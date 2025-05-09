<?php

namespace Tests\Unit\Events;

use App\Events\OrderCreated;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderCreatedTest extends TestCase
{
    public function test_it_sets_order_property()
    {
        $orderData = [
            'id' => 1,
            'product_id' => 101,
            'quantity' => 2,
            'price' => 19.99,
        ];
        $event = new OrderCreated($orderData);

        $this->assertEquals($orderData, $event->order);
    }

    public function test_it_broadcasts_on_orders_channel()
    {
        $event = new OrderCreated(['id' => 1]);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertEquals('orders', $channels[0]->name);
    }

    public function test_it_broadcasts_with_correct_event_name()
    {
        $event = new OrderCreated(['id' => 1]);

        $this->assertEquals('order.created', $event->broadcastAs());
    }

    public function test_it_implements_should_broadcast_now_interface()
    {
        $event = new OrderCreated(['id' => 1]);

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class, $event);
    }

    public function test_it_broadcasts_event_with_correct_data()
    {
        Event::fake();

        $orderData = [
            'id' => 2,
            'product_id' => 102,
            'quantity' => 3,
            'price' => 29.99,
            'product_name' => 'Test Product',
        ];
        $event = new OrderCreated($orderData);
        event($event);

        Event::assertDispatched(OrderCreated::class, function ($e) use ($orderData) {
            return $e->order === $orderData
                && $e->broadcastAs() === 'order.created'
                && $e->broadcastOn()[0]->name === 'orders';
        });
    }
}
