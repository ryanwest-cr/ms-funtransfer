<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
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
        // Pre-Middleware Action
        // Post-Middleware Action
       
        return $next($request);
    }

    public function terminate($request, $response) {
        dd($request->all());
        // echo "dsafdasf";
    }
}
