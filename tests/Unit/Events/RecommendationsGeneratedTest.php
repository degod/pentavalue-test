<?php

namespace Tests\Unit\Events;

use App\Events\RecommendationsGenerated;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecommendationsGeneratedTest extends TestCase
{
    public function test_it_sets_recommendations_property()
    {
        $recommendations = 'Increase prices for <b>umbrellas</b>.';
        $event = new RecommendationsGenerated($recommendations);

        $this->assertEquals($recommendations, $event->recommend);
    }

    public function test_it_broadcasts_on_recommendations_channel()
    {
        $event = new RecommendationsGenerated('Test recommendation');

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertEquals('recommendations', $channels[0]->name);
    }

    public function test_it_broadcasts_with_correct_event_name()
    {
        $event = new RecommendationsGenerated('Test recommendation');

        $this->assertEquals('recommendation.done', $event->broadcastAs());
    }

    public function test_it_implements_should_broadcast_now_interface()
    {
        $event = new RecommendationsGenerated('Test recommendation');

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class, $event);
    }

    public function test_it_broadcasts_event_with_correct_data()
    {
        Event::fake();

        $recommendations = 'Test <h3>Product Recommendations</h3>';
        $event = new RecommendationsGenerated($recommendations);
        event($event);

        Event::assertDispatched(RecommendationsGenerated::class, function ($e) use ($recommendations) {
            return $e->recommend === $recommendations
                && $e->broadcastAs() === 'recommendation.done'
                && $e->broadcastOn()[0]->name === 'recommendations';
        });
    }
}
