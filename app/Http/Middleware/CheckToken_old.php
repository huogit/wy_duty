<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class CheckToken_old
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
        $token = \Request::header('token');

        if (!Redis::exists($token)) 
            return response()->json(['code'=>403,'message'=>'未登录']);

         return $next($request);
    }
}
