<?php

namespace Orion\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnforceExpectsJson
{
    /**
     * @param Request $request
     * @param $next
     * @return mixed
     */
    public function handle(Request $request, $next)
    {
        if (!Str::contains($request->header('Accept'), 'application/json')) {
            $request->headers->set('Accept', 'application/json, ' . $request->header('Accept'));
        }

        return $next($request);
    }
}
