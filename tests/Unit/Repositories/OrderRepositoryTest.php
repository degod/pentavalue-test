<?php

namespace Tests\Unit\Repositories;

use App\Repositories\OrderRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use \Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OrderRepository();
        Carbon::setTestNow(Carbon::now());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_create_inserts_order_and_returns_data()
    {
        $data = [
            'product_id' => 101,
            'quantity' => 2,
            'price' => 19.99,
            'date' => Carbon::now(),
        ];

        $result = $this->repository->create($data);

        $this->assertIsArray($result);
        $this->assertEquals($data['product_id'], $result['product_id']);
        $this->assertEquals($data['quantity'], $result['quantity']);
        $this->assertEquals($data['price'], $result['price']);
        $this->assertNotNull($result['id']);
        $this->assertDatabaseHas('orders', ['id' => $result['id']]);
    }

    public function test_get_total_revenue_sums_prices()
    {
        DB::table('orders')->insert([
            ['product_id' => 1, 'quantity' => 1, 'price' => 10.00, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 2, 'quantity' => 2, 'price' => 20.00, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $revenue = $this->repository->getTotalRevenue();

        $this->assertEquals(30.00, $revenue);
    }

    public function test_get_top_products_returns_limited_grouped_data()
    {
        DB::table('orders')->insert([
            ['product_id' => 1, 'quantity' => 2, 'price' => 10.00, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 1, 'quantity' => 1, 'price' => 10.00, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 2, 'quantity' => 1, 'price' => 20.00, 'date' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $products = $this->repository->getTopProducts(2);

        $this->assertCount(2, $products);
        $this->assertEquals(1, $products[0]->product_id);
        $this->assertEquals(2, $products[0]->total_orders);
        $this->assertEquals(30.00, $products[0]->total_revenue);
        $this->assertEquals(3, $products[0]->total_quantity);
        $this->assertEquals(2, $products[1]->product_id);
    }

    public function test_get_revenue_last_minute_sums_recent_prices()
    {
        $now = Carbon::now();

        DB::table('orders')->insert([
            [
                'product_id' => 1,
                'quantity' => 1,
                'price' => 15.00,
                'date' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'product_id' => 2,
                'quantity' => 1,
                'price' => 25.00,
                'date' => $now->copy()->subMinutes(2),
                'created_at' => $now->copy()->subMinutes(2),
                'updated_at' => $now->copy()->subMinutes(2),
            ],
        ]);

        $revenue = $this->repository->getRevenueLastMinute();

        $this->assertEquals(15.00, $revenue);
    }

    public function test_get_orders_last_minute_counts_recent_orders()
    {
        $now = Carbon::now();

        DB::table('orders')->insert([
            [
                'product_id' => 1,
                'quantity' => 1,
                'price' => 10.00,
                'date' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'product_id' => 2,
                'quantity' => 1,
                'price' => 20.00,
                'date' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'product_id' => 3,
                'quantity' => 1,
                'price' => 30.00,
                'date' => $now->copy()->subMinutes(2),
                'created_at' => $now->copy()->subMinutes(2),
                'updated_at' => $now->copy()->subMinutes(2),
            ],
        ]);

        $count = $this->repository->getOrdersLastMinute();

        $this->assertEquals(2, $count);
    }
}
