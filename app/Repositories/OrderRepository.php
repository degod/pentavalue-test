<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function create(array $data): array
    {
        $id = DB::table('orders')->insertGetId([
            'product_id' => $data['product_id'],
            'quantity'   => $data['quantity'],
            'price'      => $data['price'],
            'date'       => $data['date'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('orders')->where('id', $id)->first();
    }

    public function getTotalRevenue(): float
    {
        return (float) DB::table('orders')->sum('price');
    }

    public function getTopProducts(): array
    {
        return DB::select(
            'SELECT product_id, SUM(quantity) as total_quantity
             FROM orders
             GROUP BY product_id
             ORDER BY total_quantity DESC
             LIMIT 5'
        );
    }

    public function getRevenueLastMinute(): float
    {
        return (float) DB::selectOne(
            'SELECT SUM(price) as revenue FROM orders WHERE `date` >= datetime("now", "-1 minute")'
        )?->revenue ?? 0;
    }

    public function getOrdersLastMinute(): int
    {
        return (int) DB::selectOne(
            'SELECT COUNT(*) as count FROM orders WHERE `date` >= datetime("now", "-1 minute")'
        )?->count ?? 0;
    }
}
