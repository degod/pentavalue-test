<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Events\RecommendationsGenerated;
use App\Http\Controllers\Api\OrderController;
use App\Repositories\OrderRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    public function test_store_creates_order_and_returns_response()
    {
        Event::fake([OrderCreated::class, RecommendationsGenerated::class]);

        $mockRepo = Mockery::mock(OrderRepositoryInterface::class);
        $this->app->instance(OrderRepositoryInterface::class, $mockRepo);

        $orderData = [
            'id' => 1,
            'product_id' => 123,
            'quantity' => 2,
            'price' => 49.99,
            'date' => now()->toDateTimeString()
        ];

        $mockRepo->shouldReceive('create')->once()->andReturn($orderData);
        $mockRepo->shouldReceive('getTopProducts')->once()->andReturn([
            (object)['product_id' => 123, 'total_quantity' => 20]
        ]);

        Http::fake([
            env('OPENAI_URL') => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Recommended: Discount cold drinks!']
                ]]
            ]),
            env('OPENWEATHER_URL') . '*' => Http::response([
                'main' => ['temp' => 30, 'feels_like' => 32, 'humidity' => 50],
                'weather' => [['main' => 'Clear', 'description' => 'clear sky', 'icon' => '01d']],
                'wind' => ['speed' => 3]
            ])
        ]);

        Route::post('/api/v1/order', [OrderController::class, 'store']);

        $response = $this->postJson('/api/v1/order', [
            'product_id' => 123,
            'quantity' => 2,
            'price' => 49.99,
            'date' => now()->toDateTimeString()
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'order',
            'recommendations',
            'weather'
        ]);
        Event::assertDispatched(OrderCreated::class);
        Event::assertDispatched(RecommendationsGenerated::class);
    }

    public function test_analytics_returns_aggregated_data()
    {
        $mockRepo = Mockery::mock(OrderRepositoryInterface::class);
        $this->app->instance(OrderRepositoryInterface::class, $mockRepo);

        $mockRepo->shouldReceive('getTotalRevenue')->once()->andReturn(1000);
        $mockRepo->shouldReceive('getTopProducts')->twice()->andReturn([
            (object)['product_id' => 123, 'total_quantity' => 50]
        ]);
        $mockRepo->shouldReceive('getRevenueLastMinute')->once()->andReturn(50);
        $mockRepo->shouldReceive('getOrdersLastMinute')->once()->andReturn(3);

        Http::fake([
            env('OPENAI_URL') => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Promote umbrellas during rainy days.']
                ]]
            ]),
            env('OPENWEATHER_URL') . '*' => Http::response([
                'main' => ['temp' => 20, 'feels_like' => 20, 'humidity' => 60],
                'weather' => [['main' => 'Rain', 'description' => 'light rain', 'icon' => '10d']],
                'wind' => ['speed' => 5]
            ])
        ]);

        Route::get('/api/v1/order/analytics', [OrderController::class, 'analytics']);

        $response = $this->getJson('/api/v1/order/analytics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_revenue',
            'top_products',
            'revenue_last_minute',
            'orders_last_minute',
            'recommendations',
            'weather'
        ]);
    }
}
