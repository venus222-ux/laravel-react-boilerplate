<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis as RedisAdapter;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMetricsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->path() === 'metrics') {
            return $next($request);
        }

        $start = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $start;

        // try/catch obligatoriu: o excepție necaptată aici nu trebuie să dărâme request-ul original.
        try {
            RedisAdapter::setDefaultOptions(config('prometheus.redis'));
            $registry = new CollectorRegistry(new RedisAdapter());

            $route = $request->route()?->uri() ?? $request->path();

            $counter = $registry->getOrRegisterCounter(
                config('prometheus.namespace'),
                'http_requests_total',
                'Total number of HTTP requests',
                ['method', 'route', 'status']
            );
            $counter->inc([$request->method(), $route, (string) $response->getStatusCode()]);

            $histogram = $registry->getOrRegisterHistogram(
                config('prometheus.namespace'),
                'http_request_duration_seconds',
                'HTTP request duration in seconds',
                ['method', 'route'],
                [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5]
            );
            $histogram->observe($duration, [$request->method(), $route]);
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }
}
