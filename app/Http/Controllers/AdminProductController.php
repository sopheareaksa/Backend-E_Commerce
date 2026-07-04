<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function index()
    {
        return Product::orderBy('product_id', 'desc')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_name' => 'required|string',
            'product_category' => 'required|string',
            'product_price' => 'required|numeric',
            'product_discount' => 'nullable|numeric',
            'product_special_offer' => 'nullable|integer',
            'product_image' => 'nullable|string',
            'product_image2' => 'nullable|string',
            'product_image3' => 'nullable|string',
            'product_image4' => 'nullable|string',
        ]);

        $data['created_at'] = now();
        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->validate([
            'product_name' => 'sometimes|string',
            'product_category' => 'sometimes|string',
            'product_price' => 'sometimes|numeric',
            'product_discount' => 'sometimes|numeric',
            'product_special_offer' => 'sometimes|integer',
            'product_image' => 'sometimes|string',
            'product_image2' => 'sometimes|string',
            'product_image3' => 'sometimes|string',
            'product_image4' => 'sometimes|string',
        ]);

        $product->update($data);
        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted.']);
    }
}
