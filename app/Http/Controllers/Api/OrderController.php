<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Repositories\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderRepositoryInterface $orderRepository) {}

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $order = $this->orderRepository->create([
            'product_id' => $request->input('product_id'),
            'quantity'   => $request->input('quantity'),
            'price'      => $request->input('price'),
            'date'       => now(),
        ]);

        event(new OrderCreated($order));

        return response()->json([
            'message' => 'Order created',
            'order' => $order
        ]);
    }


    public function analytics(): JsonResponse
    {
        $totalRevenue = $this->orderRepository->getTotalRevenue();
        $topProducts = $this->orderRepository->getTopProducts();
        $revenueLastMinute = $this->orderRepository->getRevenueLastMinute();
        $ordersLastMinute = $this->orderRepository->getOrdersLastMinute();

        return response()->json([
            'total_revenue' => $totalRevenue,
            'top_products' => $topProducts,
            'revenue_last_minute' => $revenueLastMinute,
            'orders_last_minute' => $ordersLastMinute,
        ]);
    }
}
