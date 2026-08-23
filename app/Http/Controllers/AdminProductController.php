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
            'product_name' => 'required|string|max:255',
            'product_category' => 'required|string|max:100',
            'product_price' => 'required|numeric',
            'product_discount' => 'nullable|numeric',
            'product_special_offer' => 'nullable|integer',
            'product_image' => 'nullable',
            'product_image2' => 'nullable',
            'product_image3' => 'nullable',
            'product_image4' => 'nullable',
        ]);

        foreach (['product_image', 'product_image2', 'product_image3', 'product_image4'] as $imgKey) {
            if ($request->hasFile($imgKey)) {
                $file = $request->file($imgKey);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('img'), $filename);
                $data[$imgKey] = $filename;
            } elseif (!$request->filled($imgKey)) {
                $data[$imgKey] = null;
            }
        }

        $data['created_at'] = now();
        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'product_category' => 'sometimes|string|max:100',
            'product_price' => 'sometimes|numeric',
            'product_discount' => 'nullable|numeric',
            'product_special_offer' => 'nullable|integer',
            'product_image' => 'nullable',
            'product_image2' => 'nullable',
            'product_image3' => 'nullable',
            'product_image4' => 'nullable',
        ]);

        foreach (['product_image', 'product_image2', 'product_image3', 'product_image4'] as $imgKey) {
            if ($request->hasFile($imgKey)) {
                $file = $request->file($imgKey);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('img'), $filename);
                $data[$imgKey] = $filename;
            } elseif ($request->has($imgKey) && is_string($request->input($imgKey))) {
                $data[$imgKey] = $request->input($imgKey);
            }
        }

        $product->update($data);
        return response()->json($product->fresh());
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted.']);
    }
}
