<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\User;

class  LoginController extends Controller
{
/**
* 获取所有
*
* @param Request $request
* @return string
*/

    private function get_New_accessToken()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.'wxe0e9860d811af920'.'&secret='.'2b594840c9684b5340d20f4997e06394';
        $res = $this->http_get($url);
        $data = json_decode($res,true);
        $accessToken = $data['access_token'];

        return $accessToken;
    }



    public function get_unionid(Request $request)
    {

        $openid = 'o99WF1mmZCiMDgUvj--bHc5EVu_s';
        $access_token = $this->get_New_accessToken();
        echo $access_token;
        $lang = 'zh_CN';

        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?';
        $query = compact('openid','access_token','lang');
        $url .= http_build_query($query);
        $data = $this->http_get($url,$query);
        $data = json_decode($data,true);

        //return $this->response(200,'ok',$data);

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

        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('APPID').'&secret='.env('APPSECRET').'&js_code='.$code.'&grant_type=authorization_code';
        $data = $this->http_get($url);

        $data = json_decode($data,true);
        	    
        if (isset($data['openid'])) 
            return $this->response(200,'ok',['openid' => $data['openid']]);
        else
            return $this->response(400,'微信服务器返回的错误',$data);
    }


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
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('APPID').'&secret='.env('APPSECRET').'&js_code='.$code.'&grant_type=authorization_code';

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
