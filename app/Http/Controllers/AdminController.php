<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return [
            'total_products' => Product::count(),
            'total_users' => User::count(),
            'total_orders' => Order::count(),
            'recent_orders' => Order::with('user')->orderBy('order_id', 'desc')->take(10)->get(),
        ];
    }

    public function users()
    {
        return User::orderBy('user_id', 'desc')->get();
    }

    public function orders()
    {
        return Order::with(['user', 'items'])->orderBy('order_id', 'desc')->get();
    }
}
