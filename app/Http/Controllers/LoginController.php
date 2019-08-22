<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\User;
use App\Utilities\WYWeChatSDK;

class  LoginController extends Controller
{
    //TODO 值班表提醒功能

    public function auth()
    {
        $sdk = new WYWeChatSDK();
        $authRedirectUrl = $sdk->getOAuthRedirectUrl('duty');
        return redirect($authRedirectUrl);
    }


    /**
     * 获取openid
     *
     * @param Request $request
     * @return string
     */
    public function get_openid(Request $request)
    {
    	$request->validate([
            'code' => 'required',
        ]);

        $code = request('code');

        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('WECHAT_MINI_PROGRAM_APPID').'&secret='.env('WECHAT_MINI_PROGRAM_SECRET').'&js_code='.$code.'&grant_type=authorization_code';
        $data = $this->http_get($url);

        $data = json_decode($data,true);
        	    
        if (isset($data['openid'])) 
            return $this->response(200,'ok',['openid' => $data['openid']]);
        else
            return $this->response(400,'微信服务器返回的错误',$data);
    }


    // TODO 登录时传信息

    /**
     * 登录
     *
     * @param Request $request
     * @return string
     */
    public function login(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);

        $code = request('code');
        //$info = request(['gender','nickName','city','provinces','country','avatarUrl']);
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('WECHAT_MINI_PROGRAM_APPID').'&secret='.env('WECHAT_MINI_PROGRAM_SECRET').'&js_code='.$code.'&grant_type=authorization_code';

        // 获取openid
        $data = $this->http_get($url);
        $data = json_decode($data,true);

        if (isset($data['openid']))
            $openid = $data['openid'];
        else
            return $data;

        // 验证身份，生成token
        $user = User::where('openid',$openid);
        if ($user->count())
            $token = JWTAuth::fromUser($user->first());
        else
            return $this->response(403,'非网园人,无权限',compact('openid'));

        // 更新用户信息
        //$user->update($info);

        return $this->response(200,'ok',compact('token'));
    }

}
