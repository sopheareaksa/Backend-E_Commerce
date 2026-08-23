<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\BakongKHQR;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BakongPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_bakong_khqr_service_generation_and_verification()
    {
        $info = [
            'bakong_account_id' => 'sopheareaksa_pheak@bkrt',
            'merchant_name' => 'test',
            'merchant_city' => 'Phnom Penh',
            'currency' => 'USD',
            'amount' => 25.50,
            'bill_number' => 'ORD-101',
            'store_label' => 'POS Store',
            'terminal_label' => 'POS-T1',
        ];

        $result = BakongKHQR::generateIndividual($info);

        $this->assertEquals(0, $result['status']['code']);
        $this->assertNotEmpty($result['data']['qr']);
        $this->assertNotEmpty($result['data']['md5']);
        $this->assertEquals(md5($result['data']['qr']), $result['data']['md5']);

        // Verify the generated QR string
        $verify = BakongKHQR::verify($result['data']['qr']);
        $this->assertEquals(0, $verify['status']['code']);
        $this->assertEquals('USD', $verify['data']['currency']);
        $this->assertEquals('25.50', $verify['data']['amount']);
        $this->assertEquals('test', $verify['data']['merchantName']);
        $this->assertEquals('Phnom Penh', $verify['data']['merchantCity']);
    }

    public function test_generate_khqr_endpoint()
    {
        $user = User::create([
            'user_name' => 'Test User',
            'user_email' => 'testuser@example.com',
            'user_password' => bcrypt('secret123'),
        ]);

        $order = Order::create([
            'order_cost' => 45.00,
            'order_status' => 'pending',
            'user_id' => $user->user_id,
            'user_phone' => '012345678',
            'user_city' => 'Phnom Penh',
            'user_address' => 'Test Address',
        ]);

        $response = $this->postJson('/api/bakong/generate-khqr', [
            'order_id' => $order->order_id,
            'currency' => 'USD',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'orderId' => $order->order_id,
                    'amount' => 45.00,
                    'currency' => 'USD',
                ],
                'message' => 'KHQR generated successfully',
            ]);

        $this->assertNotEmpty($response->json('data.qr'));
        $this->assertNotEmpty($response->json('data.md5'));
    }

    public function test_check_payment_endpoint_success()
    {
        $user = User::create([
            'user_name' => 'Test User 2',
            'user_email' => 'testuser2@example.com',
            'user_password' => bcrypt('secret123'),
        ]);

        $order = Order::create([
            'order_cost' => 45.00,
            'order_status' => 'pending',
            'user_id' => $user->user_id,
        ]);

        $fakeHash = 'a1b2c3d4e5f6g7h8';
        Http::fake([
            'https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 0,
                'responseMessage' => 'Success',
                'data' => [
                    'hash' => $fakeHash,
                    'fromAccountId' => 'customer@bkrt',
                    'toAccountId' => 'sopheareaksa_pheak@bkrt',
                    'currency' => 'USD',
                    'amount' => 45.00,
                    'description' => 'ORD-' . $order->order_id,
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/bakong/check-payment', [
            'order_id' => $order->order_id,
            'md5' => 'testmd5hash123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'COMPLETED',
                'message' => 'Payment completed and order status updated to Paid.',
            ]);

        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'order_status' => 'paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->order_id,
            'user_id' => $user->user_id,
            'transaction_id' => $fakeHash,
        ]);
    }

    public function test_verify_khqr_endpoint()
    {
        $info = [
            'bakong_account_id' => 'sopheareaksa_pheak@bkrt',
            'merchant_name' => 'test',
            'merchant_city' => 'Phnom Penh',
            'currency' => 'KHR',
            'amount' => 50000,
            'bill_number' => 'ORD-202',
        ];

        $generated = BakongKHQR::generateIndividual($info);
        $qrString = $generated['data']['qr'];

        $response = $this->postJson('/api/bakong/verify-khqr', [
            'qr_string' => $qrString,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'isValid' => true,
                ],
            ]);
    }
}
