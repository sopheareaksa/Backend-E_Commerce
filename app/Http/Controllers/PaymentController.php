<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,order_id',
        ]);

        $order = Order::where('order_id', $data['order_id'])
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();

        $transactionId = 'TXN-' . time() . '-' . rand(1000, 9999);

        Payment::create([
            'order_id' => $order->order_id,
            'user_id' => $request->user()->user_id,
            'transaction_id' => $transactionId,
            'payment_date' => now(),
        ]);

        $order->update(['order_status' => 'paid']);

        return response()->json([
            'message' => 'Payment successful.',
            'transaction_id' => $transactionId,
            'order' => $order,
        ]);
    }
}
