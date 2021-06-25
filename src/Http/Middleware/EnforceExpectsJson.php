<?php

namespace Orion\Http\Middleware;

use Illuminate\Http\Request;

class EnforceExpectsJson
{
    /**
     * @param Request $request
     * @param $next
     * @return mixed
     */
    public function handle(Request $request, $next)
    {
        if (!$request->expectsJson()) {
            $request->headers->add(['Accept' => 'application/json,' . $request->header('Accept')]);
        }
        
        return $next($request);
    }
}
