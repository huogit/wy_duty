<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try{
            if (!JWTAuth::parseToken()->check())
                exit(response()->json(['code'=>403,'message'=>'token无效']));
        }catch(\Exception $e)
        {
            exit(response()->json(['code'=>403,'message'=>'token无效']));
        }

        $user = JWTAuth::parseToken()->toUser();
        $request->offsetSet('jwt_user',$user);

        return $next($request);
    }
}
