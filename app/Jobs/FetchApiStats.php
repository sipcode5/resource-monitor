<?php

namespace App\Jobs;

use App\Events\SystemMetricsUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches stats from third-party APIs (GitHub, OpenWeatherMap, etc.)
 * without blocking the UI. Results broadcast as part of the metrics payload.
 */
class FetchApiStats implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 30;

    public function handle(): void
    {
        $stats = [];

        try {
            $stats['github'] = $this->fetchGithubStats();
        } catch (\Throwable $e) {
            Log::warning('FetchApiStats: GitHub fetch failed', ['error' => $e->getMessage()]);
            $stats['github'] = ['error' => 'unavailable'];
        }

        try {
            $stats['weather'] = $this->fetchWeather();
        } catch (\Throwable $e) {
            Log::warning('FetchApiStats: Weather fetch failed', ['error' => $e->getMessage()]);
            $stats['weather'] = ['error' => 'unavailable'];
        }

        // Merge with cached system metrics and broadcast the combined payload
        $existing = Cache::get('system_metrics', []);
        $payload  = array_merge($existing, ['api_stats' => $stats, 'timestamp' => now()->toIso8601String()]);

        Cache::put('system_metrics', $payload, now()->addMinutes(5));
        broadcast(new SystemMetricsUpdated($payload));
    }

    private function fetchGithubStats(): array
    {
        // Public rate-limit endpoint – no auth needed
        $response = Http::timeout(10)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get('https://api.github.com/rate_limit');

        if ($response->successful()) {
            return $response->json('rate') ?? [];
        }

        return ['status' => $response->status()];
    }

    private function fetchWeather(): array
    {
        $apiKey = config('services.openweather.key');
        $city   = config('services.openweather.city', 'London');

        if (empty($apiKey)) {
            return ['note' => 'Set OPENWEATHER_API_KEY in .env to enable weather stats'];
        }

        $response = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/weather', [
            'q'     => $city,
            'appid' => $apiKey,
            'units' => 'metric',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'city'        => $data['name'] ?? $city,
                'temperature' => $data['main']['temp'] ?? null,
                'feels_like'  => $data['main']['feels_like'] ?? null,
                'humidity'    => $data['main']['humidity'] ?? null,
                'description' => $data['weather'][0]['description'] ?? null,
            ];
        }

        return ['status' => $response->status()];
    }
}
