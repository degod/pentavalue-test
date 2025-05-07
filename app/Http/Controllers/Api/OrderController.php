<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        DB::insert(
            'INSERT INTO orders (product_id, quantity, price, order_date, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['product_id'],
                $data['quantity'],
                $data['price'],
                $data['order_date'],
                now(),
                now()
            ]
        );

        return response()->json(['message' => 'Order created successfully.'], 201);
    }

    public function analytics(): JsonResponse
    {
        $totalRevenue = DB::table('orders')->sum('price');

        $topProducts = DB::select(
            'SELECT product_id, SUM(quantity) as total_sold
             FROM orders
             GROUP BY product_id
             ORDER BY total_sold DESC
             LIMIT 5'
        );

        $revenueLastMinute = DB::selectOne(
            'SELECT SUM(price) as revenue FROM orders WHERE order_date >= datetime("now", "-1 minute")'
        )?->revenue ?? 0;

        $ordersLastMinute = DB::selectOne(
            'SELECT COUNT(*) as count FROM orders WHERE order_date >= datetime("now", "-1 minute")'
        )?->count ?? 0;

        return response()->json([
            'total_revenue' => (float) $totalRevenue,
            'top_products' => $topProducts,
            'revenue_last_minute' => (float) $revenueLastMinute,
            'orders_last_minute' => (int) $ordersLastMinute,
        ]);
    }
}
