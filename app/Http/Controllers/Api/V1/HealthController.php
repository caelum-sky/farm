<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => 'ok',
            'database' => 'unknown',
            'cache' => 'unknown',
            'storage' => is_writable(storage_path()) ? 'ok' : 'not_writable',
            'queue_connection' => config('queue.default', env('QUEUE_CONNECTION', 'sync')),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
        ];

        try {
            DB::select('select 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $exception) {
            $checks['database'] = 'failed';
            $checks['database_error'] = app()->environment('production') ? 'unavailable' : $exception->getMessage();
        }

        try {
            Cache::put('health_check', now()->timestamp, 10);
            $checks['cache'] = Cache::has('health_check') ? 'ok' : 'failed';
        } catch (\Throwable $exception) {
            $checks['cache'] = 'failed';
            $checks['cache_error'] = app()->environment('production') ? 'unavailable' : $exception->getMessage();
        }

        $ready = $checks['database'] === 'ok'
            && $checks['cache'] === 'ok'
            && $checks['storage'] === 'ok'
            && ! ($checks['debug'] && app()->environment('production'));

        return response()->json([
            'status' => $ready ? 'ready' : 'degraded',
            'checked_at' => now()->toISOString(),
            'checks' => $checks,
        ], $ready ? 200 : 503);
    }
}
