<?php

declare(strict_types=1);

namespace Tzmfreedom\TypeTracer\Laravel;

use Closure;

final class FuncTraceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        xdebug_set_filter(XDEBUG_FILTER_TRACING, XDEBUG_PATH_EXCLUDE, [
            base_path() . '/vendor',
        ]);
        $traceFile = sys_get_temp_dir() . '/trace.' . md5($request->path());
        xdebug_start_trace($traceFile, XDEBUG_TRACE_COMPUTERIZED);
        $response = $next($request);
        xdebug_stop_trace();
        return $response;
    }
}
