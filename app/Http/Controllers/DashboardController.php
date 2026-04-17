<?php

namespace App\Http\Controllers;

use App\Jobs\CollectSystemMetrics;
use App\Jobs\FetchApiStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class DashboardController extends Controller
{
    /**
     * Serve the SPA shell – Vue Router handles everything else.
     */
    public function index()
    {
        return view('dashboard');
    }

    /**
     * Return the latest cached metrics (used on initial page load
     * before WebSocket connection delivers live data).
     */
    public function metrics(): JsonResponse
    {
        $metrics = Cache::get('system_metrics');

        if (! $metrics) {
            // Dispatch immediately if cache is cold and return a 202
            CollectSystemMetrics::dispatch();
            FetchApiStats::dispatch();
            return response()->json(['status' => 'collecting'], 202);
        }

        // If API stats haven't been fetched yet, kick off a background fetch
        if (empty($metrics['api_stats'])) {
            FetchApiStats::dispatch();
        }

        return response()->json($metrics);
    }

    /**
     * Manually trigger a metrics collection cycle.
     * Useful for testing / "refresh now" button in the UI.
     */
    public function refresh(): JsonResponse
    {
        CollectSystemMetrics::dispatch();
        FetchApiStats::dispatch();

        return response()->json(['status' => 'dispatched']);
    }

    /**
     * Return queue depth & worker stats.
     */
    public function queueStats(): JsonResponse
    {
        $pending  = Queue::size();
        $failed   = \DB::table('failed_jobs')->count();

        return response()->json([
            'pending_jobs' => $pending,
            'failed_jobs'  => $failed,
            'timestamp'    => now()->toIso8601String(),
        ]);
    }
}
