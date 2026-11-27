<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $revenueStatuses = ['shipped', 'delivered'];

        $stats = [
            'total_revenue' => (float) Order::whereIn('status', $revenueStatuses)
                ->sum('total'),

            'total_orders' => Order::count(),

            'total_customers' => User::where('role', 'user')->count(),

            'total_products' => Product::count(),
        ];

        return response()->json([
            'status' => true,
            'data' => $stats,
        ]);
    }
}
