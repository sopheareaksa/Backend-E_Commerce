<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function createAbaCheckout(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,order_id',
        ]);

        $this->assertPaywayConfigured();
        $order = Order::with(['items', 'user'])->where('order_id', $data['order_id'])
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();

        if ($order->order_status === 'paid') {
            return response()->json(['message' => 'This order has already been paid.'], 422);
        }

        $transactionId = $order->payway_transaction_id ?: ('ORD' . $order->order_id . Str::upper(Str::random(10)));
        $order->update(['payway_transaction_id' => $transactionId]);
        $names = preg_split('/\s+/', trim($order->user->user_name ?? 'Customer'), 2);
        $frontendUrl = rtrim(config('services.payway.frontend_url'), '/');
        $successUrl = $frontendUrl . '/payment?order_id=' . $order->order_id . '&paid=1';
        $cancelUrl = $frontendUrl . '/payment?order_id=' . $order->order_id . '&cancelled=1';

        $fields = [
            'req_time' => now('UTC')->format('YmdHis'),
            'merchant_id' => config('services.payway.merchant_id'),
            'tran_id' => $transactionId,
            'amount' => number_format((float) $order->order_cost, 2, '.', ''),
            'items' => base64_encode(json_encode($order->items->map(fn ($item) => ['name' => $item->product_name, 'quantity' => (int) $item->product_quantity, 'price' => (float) $item->product_price])->values()->all())),
            'shipping' => '0',
            'firstname' => $names[0] ?? 'Customer',
            'lastname' => $names[1] ?? '',
            'email' => $order->user->user_email,
            'phone' => $order->user_phone ?? '',
            'type' => 'purchase',
            'payment_option' => '',
            'return_url' => base64_encode(config('services.payway.return_url')),
            'cancel_url' => base64_encode($cancelUrl),
            'continue_success_url' => base64_encode($successUrl),
            'return_deeplink' => '',
            'currency' => config('services.payway.currency'),
            'custom_fields' => '',
            'return_params' => '',
            'payout' => '',
            'lifetime' => '',
            'additional_params' => '',
            'google_pay_token' => '',
            'skip_success_page' => '1',
        ];
        $fields['hash'] = $this->purchaseHash($fields);

        $response = Http::asForm()->timeout(20)->post(config('services.payway.purchase_url'), $fields);
        $payment = $response->json();
        $statusCode = data_get($payment, 'status.code');

        if (!$response->successful() || !in_array((string) $statusCode, ['0', '00'], true)) {
            Log::warning('ABA PayWay purchase creation failed.', [
                'order_id' => $order->order_id,
                'transaction_id' => $transactionId,
                'http_status' => $response->status(),
                'payway_status' => $statusCode,
            ]);
            return response()->json([
                'message' => data_get($payment, 'status.message', 'ABA PayWay could not create the payment request.'),
            ], 422);
        }

        return response()->json([
            'payment' => $payment,
            'transaction_id' => $transactionId,
        ]);
    }

    public function abaCallback(Request $request)
    {
        $payload = $request->json()->all();
        if (!$this->validCallbackSignature($payload, $request->header('X-PAYWAY-HMAC-SHA512', ''))) {
            Log::warning('ABA PayWay callback rejected: invalid signature.');
            return response()->json(['message' => 'Invalid signature.'], 401);
        }
        $order = isset($payload['tran_id']) ? Order::where('payway_transaction_id', $payload['tran_id'])->first() : null;
        if (!$order) return response()->json(['message' => 'Transaction not found.'], 404);
        $this->confirmPayment($order);
        return response()->json(['message' => 'Callback received.']);
    }

    public function paymentStatus(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->user_id, 404);
        if ($order->order_status !== 'paid' && $order->payway_transaction_id) {
            $this->confirmPayment($order);
            $order->refresh();
        }
        return response()->json(['order_id' => $order->order_id, 'status' => $order->order_status, 'paid' => $order->order_status === 'paid']);
    }

    /**
     * Creates a local-only paid transaction for checkout testing. This route is
     * deliberately unavailable in staging and production environments.
     */
    public function simulatePayment(Request $request, Order $order)
    {
        abort_unless(app()->environment('local'), 404);
        abort_unless($order->user_id === $request->user()->user_id, 404);

        if ($order->order_status === 'paid') {
            return response()->json(['message' => 'This order has already been paid.'], 422);
        }

        $transactionId = $order->payway_transaction_id ?: ('DEV' . Str::upper(Str::random(17)));
        $order->update([
            'payway_transaction_id' => $transactionId,
            'order_status' => 'paid',
        ]);

        Payment::firstOrCreate(
            ['transaction_id' => $transactionId],
            ['order_id' => $order->order_id, 'user_id' => $order->user_id, 'payment_date' => now()]
        );

        return response()->json([
            'message' => 'Test payment recorded successfully.',
            'order_id' => $order->order_id,
            'transaction_id' => $transactionId,
            'paid' => true,
        ]);
    }

    private function confirmPayment(Order $order): void
    {
        $response = $this->checkTransaction($order->payway_transaction_id);
        if (($response['data']['payment_status'] ?? null) !== 'APPROVED') return;
        Payment::firstOrCreate(['transaction_id' => $order->payway_transaction_id], ['order_id' => $order->order_id, 'user_id' => $order->user_id, 'payment_date' => now()]);
        $order->update(['order_status' => 'paid']);
    }

    private function checkTransaction(string $transactionId): array
    {
        $reqTime = now('UTC')->format('YmdHis');
        $merchantId = config('services.payway.merchant_id');
        $hash = base64_encode(hash_hmac('sha512', $reqTime . $merchantId . $transactionId, config('services.payway.api_key'), true));
        $response = Http::timeout(10)->acceptJson()->post(config('services.payway.check_url'), ['req_time' => $reqTime, 'merchant_id' => $merchantId, 'tran_id' => $transactionId, 'hash' => $hash]);
        if (!$response->successful()) {
            Log::warning('ABA PayWay transaction check failed.', ['transaction_id' => $transactionId, 'status' => $response->status()]);
            return [];
        }
        return $response->json();
    }

    private function purchaseHash(array $fields): string
    {
        $keys = ['req_time', 'merchant_id', 'tran_id', 'amount', 'items', 'shipping', 'firstname', 'lastname', 'email', 'phone', 'type', 'payment_option', 'return_url', 'cancel_url', 'continue_success_url', 'return_deeplink', 'currency', 'custom_fields', 'return_params', 'payout', 'lifetime', 'additional_params', 'google_pay_token', 'skip_success_page'];
        return base64_encode(hash_hmac('sha512', implode('', array_map(fn ($key) => $fields[$key], $keys)), config('services.payway.api_key'), true));
    }

    private function validCallbackSignature(array $payload, string $receivedSignature): bool
    {
        if (!$payload || !$receivedSignature) return false;
        ksort($payload);
        $hashInput = '';
        foreach ($payload as $value) $hashInput .= is_array($value) ? json_encode($value) : $value;
        $signature = base64_encode(hash_hmac('sha512', $hashInput, config('services.payway.api_key'), true));
        return hash_equals($signature, $receivedSignature);
    }

    private function assertPaywayConfigured(): void
    {
        foreach (['merchant_id', 'api_key', 'return_url'] as $setting) {
            if (!config('services.payway.' . $setting)) abort(503, 'ABA PayWay has not been configured.');
        }
    }

    /**
     * Bakong KHQR: 1. Generate KHQR for an existing Order
     */
    public function generateKhqr(Request $request)
    {
        return app(BakongPaymentController::class)->generateKhqr($request);
    }

    /**
     * Bakong KHQR: 2. Check Payment Status via Bakong API & Update Database
     */
    public function checkPayment(Request $request)
    {
        return app(BakongPaymentController::class)->checkPayment($request);
    }

    /**
     * Bakong KHQR: 3. Verify KHQR String
     */
    public function verifyKhqr(Request $request)
    {
        return app(BakongPaymentController::class)->verifyKhqr($request);
    }
}

