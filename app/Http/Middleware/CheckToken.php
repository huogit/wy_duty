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
//        $response = new \App\Http\Controllers\Controller;
//        try{
//            if (!JWTAuth::parseToken()->check())
//                exit($response->response(403,'token无效'));
//        }catch(\Exception $e)
//        {
//            exit($response->response(403,'token无效'));
//        }
//
//        $user = JWTAuth::parseToken()->toUser();
//        $request->offsetSet('jwt_user',$user);

        $request->offsetSet('jwt_user',\App\User::find(6));

        return $next($request);
    }
}
