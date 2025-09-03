<?php

namespace App\Http\Controllers;

use App\Services\ResellerApi\ResellerApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class HealthCheckController extends Controller
{
    /**
     * Health check endpoint for monitoring
     */
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];
        $overallStatus = 200;

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'response_time' => $this->measureTime(fn() => DB::select('SELECT 1'))
            ];
        } catch (Exception $e) {
            $checks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
            $status = 'degraded';
            $overallStatus = 503;
        }

        // Cache check (Redis/File)
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            $checks['cache'] = [
                'status' => $retrieved === 'test' ? 'healthy' : 'unhealthy',
                'message' => $retrieved === 'test' ? 'Cache working properly' : 'Cache retrieval failed',
                'driver' => config('cache.default')
            ];
        } catch (Exception $e) {
            $checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache error: ' . $e->getMessage()
            ];
            $status = 'degraded';
        }

        // DataImpulse API check
        try {
            $apiClient = app(ResellerApiClient::class);
            $startTime = microtime(true);
            $balance = $apiClient->getUserBalance();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $checks['dataimpulse_api'] = [
                'status' => 'healthy',
                'message' => 'DataImpulse API accessible',
                'response_time' => $responseTime . 'ms',
                'balance' => $balance['balance'] ?? 'N/A'
            ];
        } catch (Exception $e) {
            $checks['dataimpulse_api'] = [
                'status' => 'unhealthy',
                'message' => 'DataImpulse API error: ' . $e->getMessage()
            ];
            // API failure doesn't make service unhealthy due to fallback logic
        }

        // Disk space check
        $diskUsage = disk_free_space(storage_path()) / disk_total_space(storage_path()) * 100;
        $checks['disk_space'] = [
            'status' => $diskUsage > 10 ? 'healthy' : 'warning',
            'message' => round(100 - $diskUsage, 1) . '% disk usage',
            'free_space' => $this->formatBytes(disk_free_space(storage_path()))
        ];

        // Memory check
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $checks['memory'] = [
            'status' => 'healthy',
            'current_usage' => $this->formatBytes($memoryUsage),
            'peak_usage' => $this->formatBytes($memoryPeak)
        ];

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => $checks
        ], $overallStatus);
    }

    /**
     * Simple health check for load balancers
     */
    public function ping(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Measure execution time of a callback
     */
    private function measureTime(callable $callback): string
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2) . 'ms';
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
}
