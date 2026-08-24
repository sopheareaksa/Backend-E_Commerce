<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\BakongKHQR;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BakongPaymentController extends Controller
{
    /**
     * 1. Generate KHQR for an existing Order
     */
    public function generateKhqr(Request $request)
    {
        try {
            $orderId = $request->input('order_id') ?? $request->input('OrderId') ?? $request->input('orderId');
            $currency = $request->input('currency') ?? $request->input('Currency') ?? 'USD';

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'OrderId is required',
                ], 422);
            }

            $order = Order::where('order_id', $orderId)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            if (strcasecmp((string) $order->order_status, 'paid') === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already paid',
                ], 400);
            }

            $bakongAccountId = config('services.bakong.account_id') ?: 'sopheareaksa_pheak@bkrt';
            $merchantId = config('services.bakong.merchant_id') ?: $bakongAccountId;
            $acquiringBank = config('services.bakong.acquiring_bank') ?: 'Bakong';
            $accountType = config('services.bakong.account_type') ?: 'individual';
            $merchantName = config('services.bakong.merchant_name') ?: 'Sopheareaksa Pheak';
            $merchantCity = config('services.bakong.merchant_city') ?: 'Phnom Penh';

            $isKhr = strcasecmp((string) $currency, 'KHR') === 0;
            $selectedCurrency = $isKhr ? 'KHR' : 'USD';

            $orderCost = (float) ($order->order_cost ?? $order->total_cost ?? 0);

            $info = [
                'bakong_account_id' => $bakongAccountId,
                'merchant_id' => $merchantId,
                'acquiring_bank' => $acquiringBank,
                'account_type' => $accountType,
                'merchant_name' => $merchantName,
                'merchant_city' => $merchantCity,
                'currency' => $selectedCurrency,
                'amount' => $orderCost,
                'bill_number' => "ORD-{$order->order_id}",
                'store_label' => 'POS Store',
                'terminal_label' => 'POS-T1',
            ];

            $response = BakongKHQR::generate($info);

            if (($response['status']['code'] ?? -1) === 0 && !empty($response['data'])) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'orderId' => $order->order_id,
                        'qr' => $response['data']['qr'],
                        'md5' => $response['data']['md5'],
                        'amount' => $orderCost,
                        'currency' => $selectedCurrency,
                    ],
                    'message' => 'KHQR generated successfully',
                ]);
            }

            $statusCode = $response['status']['code'] ?? 500;
            $statusMsg = $response['status']['message'] ?? 'Unknown error';
            Log::warning("KHQR generation failed. Status Code: {$statusCode}, Message: {$statusMsg}");

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate KHQR',
                'debug' => [
                    'statusCode' => $statusCode,
                    'statusMessage' => $statusMsg,
                ],
            ], 500);
        } catch (Exception $ex) {
            Log::error('Error generating KHQR', ['exception' => $ex->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. Check Payment Status via Bakong API & Update Database
     */
    public function checkPayment(Request $request)
    {
        try {
            $orderId = $request->input('order_id') ?? $request->input('OrderId') ?? $request->input('orderId');
            $md5 = trim((string) ($request->input('md5') ?? $request->input('MD5') ?? ''));

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'OrderId is required',
                ], 422);
            }

            $order = Order::where('order_id', $orderId)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            if (strcasecmp((string) $order->order_status, 'paid') === 0) {
                return response()->json([
                    'success' => true,
                    'status' => 'COMPLETED',
                    'message' => 'Order is already verified and paid',
                ]);
            }

            $apiUrl = rtrim(config('services.bakong.api_url') ?: 'https://api-bakong.nbc.gov.kh', '/');
            $apiToken = config('services.bakong.api_token') ?: '';

            $httpClient = Http::withoutVerifying()->timeout(10);
            if (!empty($apiToken)) {
                $httpClient = $httpClient->withToken($apiToken);
            }

            $httpResponse = $httpClient->post("{$apiUrl}/v1/check_transaction_by_md5", [
                'md5' => $md5,
            ]);

            Log::debug('Bakong check_transaction_by_md5 response', [
                'status' => $httpResponse->status(),
                'body' => $httpResponse->json() ?? $httpResponse->body(),
            ]);

            if ($httpResponse->successful()) {
                $result = $httpResponse->json();
                $responseCode = data_get($result, 'responseCode');

                if ($responseCode === 0) {
                    $transactionHash = data_get($result, 'data.hash') ?: $md5;

                    // 1. Update Order status
                    $order->update(['order_status' => 'paid']);

                    // 2. Record new Payment row
                    $payment = Payment::firstOrCreate(
                        ['transaction_id' => $transactionHash],
                        [
                            'order_id' => $order->order_id,
                            'user_id' => $order->user_id,
                            'payment_date' => now(),
                        ]
                    );

                    return response()->json([
                        'success' => true,
                        'status' => 'COMPLETED',
                        'message' => 'Payment completed and order status updated to Paid.',
                    ]);
                }
            }

            $result = $httpResponse->json();
            $msg = data_get($result, 'responseMessage') ?: 'Payment pending or not found';

            return response()->json([
                'success' => false,
                'status' => 'PENDING',
                'message' => $msg,
                'debug' => [
                    'api_token_set' => !empty($apiToken),
                    'response' => $result,
                ],
            ]);
        } catch (Exception $ex) {
            Log::error('Error checking payment', ['exception' => $ex->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. Simulate / Confirm Bakong Payment (Useful for testing / manual confirmation)
     */
    public function simulatePayment(Request $request)
    {
        try {
            $orderId = $request->input('order_id') ?? $request->input('OrderId') ?? $request->input('orderId');

            if (!$orderId) {
                return response()->json(['success' => false, 'message' => 'OrderId is required'], 422);
            }

            $order = Order::where('order_id', $orderId)->first();
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            $order->update(['order_status' => 'paid']);
            $hash = 'BKNG-TXN-' . strtoupper(bin2hex(random_bytes(8)));

            Payment::firstOrCreate(
                ['transaction_id' => $hash],
                [
                    'order_id' => $order->order_id,
                    'user_id' => $order->user_id,
                    'payment_date' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'status' => 'COMPLETED',
                'transaction_id' => $hash,
                'message' => 'Payment marked as completed successfully.',
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. Verify KHQR String
     */
    public function verifyKhqr(Request $request)
    {
        try {
            $qrString = $request->input('qr_string') ?? $request->input('qrString') ?? $request->input('QrString');

            if (empty($qrString)) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR String is required',
                ], 400);
            }

            $verifyResult = BakongKHQR::verify($qrString);
            $isValid = ($verifyResult['status']['code'] ?? -1) === 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'isValid' => $isValid,
                    'decoded' => $verifyResult['data'] ?? null,
                ],
            ]);
        } catch (Exception $ex) {
            Log::error('Error verifying KHQR string', ['exception' => $ex->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
            ], 500);
        }
    }
}
