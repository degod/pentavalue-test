<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RecommendationsGenerated implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public $recommend;

    public function __construct(string $recommendations)
    {
        $this->recommend = $recommendations;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('recommendations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'recommendation.done';
    }
}
