<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $items = CartItem::where('user_id', $request->user()->user_id)
            ->with('product')
            ->get();

        return $items->map(function ($item) {
            return [
                'cart_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->product_name,
                'product_price' => $item->product->product_price,
                'product_discount' => $item->product->product_discount,
                'product_image' => $item->product->product_image,
                'quantity' => $item->quantity,
            ];
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,product_id',
            'quantity' => 'integer|min:1',
        ]);

        $item = CartItem::updateOrCreate(
            [
                'user_id' => $request->user()->user_id,
                'product_id' => $data['product_id'],
            ],
            ['quantity' => $data['quantity'] ?? 1]
        );

        return response()->json($item, 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item = CartItem::where('id', $id)
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();

        $item->update(['quantity' => $data['quantity']]);
        return response()->json($item);
    }

    public function destroy(Request $request, $id)
    {
        $item = CartItem::where('id', $id)
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();

        $item->delete();
        return response()->json(['message' => 'Removed from cart.']);
    }

    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->user_id)->delete();
        return response()->json(['message' => 'Cart cleared.']);
    }
}
