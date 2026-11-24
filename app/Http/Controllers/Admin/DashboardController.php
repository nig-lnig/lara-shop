<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'clients' => Client::whereNull('archived_at')->count(),
            'suppliers' => Supplier::whereNull('archived_at')->count(),
            'products' => Product::available()->count(),
            'orders' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            // Revenue of non-cancelled orders (SUM on DECIMAL columns stays exact in SQL).
            'revenue' => Order::where('status', '!=', 'cancelled')->sum('total'),
        ];

        $recentOrders = Order::with('client')->latest('order_date')->latest('id')->limit(8)->get();

        $lowStock = Product::available()->lowStock()->with('supplier')->orderBy('stock')->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStock'));
    }
}
