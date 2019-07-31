<?php

namespace App\Http\Middleware;

use Closure;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->jwt_user->is_admin)
            return response()->json(['code'=>403,'message'=>'非管理员,无权限']);

        return $next($request);
    }
}
