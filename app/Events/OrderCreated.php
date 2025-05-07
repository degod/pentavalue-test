<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;

class OrderCreated implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct(public array $orderData) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }
}
