<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::all();
    }

    public function featured()
    {
        return Product::where('product_category', 'featured')->get();
    }

    public function byCategory($slug)
    {
        return Product::where('product_category', $slug)->get();
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        return Product::where('product_name', 'like', "%{$q}%")->get();
    }

    public function show($id)
    {
        return Product::findOrFail($id);
    }
}
