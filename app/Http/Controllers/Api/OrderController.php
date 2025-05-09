<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Events\RecommendationsGenerated;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Repositories\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    private $defaultCity = 'Lagos';
    private $defaultCountry = 'NG';

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

        $weatherData = $this->getWeatherData();
        $recommendations = "";
        if ($weatherData) {
            $recommendations = $this->generateRecommendations($weatherData);
        }
        event(new RecommendationsGenerated($recommendations));

        return response()->json([
            'message' => 'Order created',
            'order' => $order,
            'recommendations' => $recommendations,
            'weather' => $weatherData
        ]);
    }

    public function analytics()
    {
        $totalRevenue = $this->orderRepository->getTotalRevenue();
        $topProducts = $this->orderRepository->getTopProducts();
        $revenueLastMinute = $this->orderRepository->getRevenueLastMinute();
        $ordersLastMinute = $this->orderRepository->getOrdersLastMinute();

        $weatherData = $this->getWeatherData();
        $recommendations = "";
        if ($weatherData) {
            $recommendations = $this->generateRecommendations($weatherData);
        }

        return response()->json([
            'total_revenue' => $totalRevenue,
            'top_products' => $topProducts,
            'revenue_last_minute' => $revenueLastMinute,
            'orders_last_minute' => $ordersLastMinute,
            'recommendations' => $recommendations,
            'weather' => $weatherData
        ]);
    }

    private function generateRecommendations(array $weather = []): string
    {
        $topProducts = $this->orderRepository->getTopProducts();

        $salesData = collect($topProducts)->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'total_quantity' => $item->total_quantity
            ];
        })->toArray();

        $prompt = "Given this sales data, which products should we promote for higher revenue?\n\n" .
            json_encode($salesData, JSON_PRETTY_PRINT);

        if ($weather) {
            $season = now()->format('F');
            $weatherSummary = "Current temperature is {$weather['temperature']}°C with {$weather['description']}.";

            $weatherPrompt = "Weather Info: $weatherSummary\n\n";
            $weatherPrompt .= "Current Month: $season\n\n";
            $weatherPrompt .= "You are a retail pricing analyst. Recommend how to adjust product pricing based on this data (e.g., increase prices for umbrellas if it's rainy, discount cold drinks if it's hot, etc.)";

            $prompt .= $weatherPrompt;
        }

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post(env('OPENAI_URL'), [
                'model' => env('OPENAI_MODEL'),
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a retail marketing expert.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7
            ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content') ?? 'No recommendation available.';
            $formattedContent = preg_replace('/###\s*(.*?)\s*:\n\n/', '<h3>$1</h3>', $content);
            $formattedContent = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $formattedContent);
            return $formattedContent;
        } else {
            Log::error('OpenAI API error: ' . $response->body());
            return 'Unable to generate recommendations at this time.';
        }
    }

    private function getWeatherData(): array
    {
        try {
            $apiKey = env('OPENWEATHER_API_KEY');
            $city = env('WEATHER_CITY', $this->defaultCity);
            $country = env('WEATHER_COUNTRY', $this->defaultCountry);

            if (!$apiKey) {
                Log::warning('OpenWeather API key not configured');
                return $this->getDefaultWeatherData();
            }

            $response = Http::get(env('OPENWEATHER_URL'), [
                'q' => "{$city},{$country}",
                'APPID' => $apiKey,
                'units' => 'metric'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'temperature' => $data['main']['temp'] ?? null,
                    'feels_like' => $data['main']['feels_like'] ?? null,
                    'condition' => $data['weather'][0]['main'] ?? null,
                    'description' => $data['weather'][0]['description'] ?? null,
                    'icon' => $data['weather'][0]['icon'] ?? null,
                    'humidity' => $data['main']['humidity'] ?? null,
                    'wind_speed' => $data['wind']['speed'] ?? null,
                    'location' => [
                        'city' => $city,
                        'country' => $country
                    ],
                    'is_hot' => ($data['main']['temp'] ?? 20) > 25,
                    'is_cold' => ($data['main']['temp'] ?? 20) < 10,
                    'is_rainy' => in_array(strtolower($data['weather'][0]['main'] ?? ''), ['rain', 'drizzle', 'thunderstorm']),
                    'timestamp' => now()->timestamp
                ];
            } else {
                Log::error('Weather API error: ' . $response->body());
                return $this->getDefaultWeatherData();
            }
        } catch (\Exception $e) {
            Log::error('Error fetching weather data: ' . $e->getMessage());
            return $this->getDefaultWeatherData();
        }
    }

    private function getDefaultWeatherData(): array
    {
        $city = $this->defaultCity;
        $country = $this->defaultCountry;

        return [
            'temperature' => 28,
            'feels_like' => 20,
            'condition' => 'Clear',
            'description' => 'clear sky',
            'icon' => '01d',
            'humidity' => 50,
            'wind_speed' => 3,
            'location' => [
                'city' => $city,
                'country' => $country
            ],
            'is_hot' => false,
            'is_cold' => false,
            'is_rainy' => false,
            'timestamp' => now()->timestamp,
            'is_default' => true
        ];
    }
}
