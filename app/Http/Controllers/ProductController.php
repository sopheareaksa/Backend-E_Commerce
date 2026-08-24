<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function cachedResponse($data)
    {
        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
    }

    public function index()
    {
        return $this->cachedResponse(Product::orderBy('product_id', 'desc')->get());
    }

    public function featured()
    {
        return $this->cachedResponse(Product::where('product_category', 'featured')->orderBy('product_id', 'desc')->get());
    }

    public function byCategory($slug)
    {
        return $this->cachedResponse(Product::where('product_category', $slug)->orderBy('product_id', 'desc')->get());
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        return $this->cachedResponse(Product::where('product_name', 'like', "%{$q}%")->orderBy('product_id', 'desc')->get());
    }

    public function show($id)
    {
        return $this->cachedResponse(Product::findOrFail($id));
    }
}
