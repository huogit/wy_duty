<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;


class CheckAdmin_old
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
        $openid = Redis::get($token);
        $user = DB::table('users')->where('openid',$openid);

        // 判断数据库中有无此openid,没有则代表该用户已被删除，应该删除其token
        if ($user->count() == 0)
        {
            Redis::del($token);
            return response()->json(['code'=>403,'message'=>'未登录']);
        }
        else
        {
            $is_admin = $user->select('is_admin')->first()->is_admin; 
        }
        
        if ($is_admin == 0)
            return response()->json(['code'=>403,'message'=>'非管理员,无权限']);

         return $next($request);
    }
}
