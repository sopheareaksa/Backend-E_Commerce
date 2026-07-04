<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_phone' => 'required|string',
            'user_city' => 'required|string',
            'user_address' => 'required|string',
        ]);

        $user = $request->user();
        $cartItems = CartItem::where('user_id', $user->user_id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $orderCost = 0;
        foreach ($cartItems as $item) {
            $price = $item->product->product_discount > 0
                ? $item->product->product_discount
                : $item->product->product_price;
            $orderCost += $price * $item->quantity;
        }

        DB::beginTransaction();
        try {
            $order = Order::create([
                'order_cost' => $orderCost,
                'order_status' => 'confirmed',
                'user_id' => $user->user_id,
                'user_phone' => $data['user_phone'],
                'user_city' => $data['user_city'],
                'user_address' => $data['user_address'],
                'order_date' => now(),
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name,
                    'product_image' => $item->product->product_image,
                    'product_price' => $item->product->product_price,
                    'product_quantity' => $item->quantity,
                    'user_id' => $user->user_id,
                    'order_date' => now(),
                ]);
            }

            CartItem::where('user_id', $user->user_id)->delete();
            DB::commit();

            return response()->json($order->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Order failed.', 'error' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        return Order::where('user_id', $request->user()->user_id)
            ->with('items')
            ->orderBy('order_id', 'desc')
            ->get();
    }

    public function show(Request $request, $id)
    {
        $order = Order::where('order_id', $id)
            ->where('user_id', $request->user()->user_id)
            ->with('items')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json($order);
    }
}
