<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::orderBy('product_id', 'desc')->get();
    }

    public function featured()
    {
        return Product::where('product_category', 'featured')->orderBy('product_id', 'desc')->get();
    }

    public function byCategory($slug)
    {
        return Product::where('product_category', $slug)->orderBy('product_id', 'desc')->get();
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        return Product::where('product_name', 'like', "%{$q}%")->orderBy('product_id', 'desc')->get();
    }

    public function show($id)
    {
        return Product::findOrFail($id);
    }
}
