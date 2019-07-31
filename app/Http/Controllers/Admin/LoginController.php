<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginController extends \App\Http\Controllers\Controller
{

    /**
     * 登录
     *
     * @param Request $request
     * @return string
     */
   public function login(Request $request)
   {
       $request->validate([
           'username' => 'required',
           'password' => 'required|max:16',
           'is_remember' => 'required|boolean',
       ]);

       $is_remember =  boolval(request('is_remember'));
       $user = request(['username','password']);

        if (Auth::guard('admin')->attempt($user,$is_remember))
           return $this->response(200,'登录成功');
        else
           return $this->response(403,'用户名或密码错误');
   }

    /**
     * 登出
     *
     * @param Request $request
     * @return string
     */
    public function logout(Request $request)
    {
        // 可能是因为用的不是自带的Auth，就获取不到用户，清不掉session
        $request->session()->flush();

        return $this->response(200,'退出登录成功');
    } 

}
