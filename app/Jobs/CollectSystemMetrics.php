<?php

namespace App\Jobs;

use App\Events\AlertTriggered;
use App\Events\SystemMetricsUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Collects system-level metrics (CPU, memory, disk, load average)
 * and broadcasts them via WebSocket. Scheduled every minute.
 */
class CollectSystemMetrics implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    public function handle(): void
    {
        $metrics = $this->gatherMetrics();

        // Persist to cache so the initial HTTP load can serve them instantly
        Cache::put('system_metrics', $metrics, now()->addMinutes(5));

        // Broadcast live update to all dashboard clients
        broadcast(new SystemMetricsUpdated($metrics));

        // Fire alerts when thresholds are breached
        $this->checkThresholds($metrics);
    }

    private function gatherMetrics(): array
    {
        return [
            'cpu_percent'    => $this->cpuUsage(),
            'memory'         => $this->memoryUsage(),
            'disk'           => $this->diskUsage(),
            'load_average'   => sys_getloadavg(),
            'uptime_seconds' => $this->uptime(),
            'timestamp'      => now()->toIso8601String(),
        ];
    }

    private function cpuUsage(): float
    {
        // Two-sample approach for a quick estimate on Linux/macOS
        $load = sys_getloadavg();
        $cores = (int) (shell_exec('nproc 2>/dev/null || sysctl -n hw.ncpu 2>/dev/null') ?: 1);

        return round(min(($load[0] / max($cores, 1)) * 100, 100), 1);
    }

    private function memoryUsage(): array
    {
        $total     = 0;
        $available = 0;

        if (PHP_OS_FAMILY === 'Linux') {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total_m);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail_m);
            $total     = isset($total_m[1]) ? (int) $total_m[1] * 1024 : 0;
            $available = isset($avail_m[1]) ? (int) $avail_m[1] * 1024 : 0;
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $total     = (int) trim(shell_exec('sysctl -n hw.memsize') ?: '0');
            $vmStat    = shell_exec('vm_stat') ?: '';
            preg_match('/Pages free:\s+(\d+)/', $vmStat, $free_m);
            preg_match('/Pages inactive:\s+(\d+)/', $vmStat, $inactive_m);
            $pageSize  = 4096;
            $free      = isset($free_m[1]) ? (int) $free_m[1] : 0;
            $inactive  = isset($inactive_m[1]) ? (int) $inactive_m[1] : 0;
            $available = ($free + $inactive) * $pageSize;
        }

        $used    = $total - $available;
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        return [
            'total_bytes'     => $total,
            'used_bytes'      => $used,
            'available_bytes' => $available,
            'used_percent'    => $percent,
        ];
    }

    private function diskUsage(): array
    {
        $path  = base_path();
        $total = disk_total_space($path);
        $free  = disk_free_space($path);
        $used  = $total - $free;

        return [
            'total_bytes'  => $total,
            'used_bytes'   => $used,
            'free_bytes'   => $free,
            'used_percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
        ];
    }

    private function uptime(): int
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $raw = file_get_contents('/proc/uptime');
            return (int) explode(' ', $raw)[0];
        }
        if (PHP_OS_FAMILY === 'Darwin') {
            preg_match('/sec = (\d+)/', shell_exec('sysctl -n kern.boottime') ?: '', $m);
            return isset($m[1]) ? time() - (int) $m[1] : 0;
        }
        return 0;
    }

    private function checkThresholds(array $metrics): void
    {
        if ($metrics['cpu_percent'] >= 90) {
            broadcast(new AlertTriggered(
                'cpu',
                "CPU usage critical: {$metrics['cpu_percent']}%",
                'critical',
                ['value' => $metrics['cpu_percent']]
            ));
        } elseif ($metrics['cpu_percent'] >= 75) {
            broadcast(new AlertTriggered(
                'cpu',
                "CPU usage high: {$metrics['cpu_percent']}%",
                'warning',
                ['value' => $metrics['cpu_percent']]
            ));
        }

        $memPercent = $metrics['memory']['used_percent'] ?? 0;
        if ($memPercent >= 90) {
            broadcast(new AlertTriggered(
                'memory',
                "Memory usage critical: {$memPercent}%",
                'critical',
                ['value' => $memPercent]
            ));
        }

        $diskPercent = $metrics['disk']['used_percent'] ?? 0;
        if ($diskPercent >= 85) {
            broadcast(new AlertTriggered(
                'disk',
                "Disk usage high: {$diskPercent}%",
                'warning',
                ['value' => $diskPercent]
            ));
        }
    }
}
