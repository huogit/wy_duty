<?php

namespace App\Http\Controllers;

use App\AddressBook;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * 我的信息
     *
     * @return string
     */
    public function me()
    {
        $openid = (request('jwt_user'))->openid;
        $userInfo = DB::table('users')->where('openid',$openid)
            ->select('department','class','major','email','phone','wechat_id','duty_count','complements_count','leaves_count')
            ->first();

        return $this->response(200,'ok',$userInfo);
    }

    /**
     * 更新我的信息
     *
     * @param Request $request
     * @return string
     */
    public function update(Request $request)
    {
        $request->validate([
            'department' => 'in:0,1,2,3' ,
            'class' => 'numeric',
            //'phone' => 'regex:/^1[34578]\d{9}$/',
            //'major' => 'exists:majors,major'
        ]);

        $user = request('jwt_user');

        $params = request(['department','class','major','email','phone','wechat_id']);
        $params = array_diff($params,[null]); // 过滤空的值，因为不一定更新所有的数据
        $user->update($params);
        AddressBook::where("user_id",$user->openid)->update($params);

        return $this->response(200,'ok');
    }
   
    /**
    * 检查token是否过期
     *
    * @return string
    */
    public function checkToken()
    {
        if (JWTAuth::parseToken()->check())
            return $this->response(200, 'token有效');
        else
            return $this->response(204,'token无效');
    }
}
