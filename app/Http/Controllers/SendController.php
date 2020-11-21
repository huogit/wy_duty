<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class  SendController
{
    //订阅消息
    public static function sendMessage($openid = "")
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WECHAT_MINI_PROGRAM_APPID'). '&secret='.env('WECHAT_MINI_PROGRAM_SECRET');
        $res = json_decode(file_get_contents($url),true);
        $access_token = $res['access_token'] ;

        //请求url
        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$access_token ;
        $data = [] ;
        $data['touser'] = $openid;
        //订阅模板id
        $data['template_id'] = 'D0MzA36f6sZfWms-gfpR5XhzU6kgTzIPvHou8ZvY1Ds' ;
        //点击模板卡片后的跳转页面
        $data['page'] = 'pages/setting/setting' ;
        $data['data'] = [
            "thing9"=>[
                'value' => '琼姐提醒你，再不去签到，就哭给你看'//签到时间要过期了@·~·@
            ],
            "time12"=>[
                 'value' => date("Y-m-d H:i:s")
            ]
        ];
        //跳转小程序类型：developer为开发版；trial为体验版；formal为正式版；默认为正式版
        $data['miniprogram_state'] = 'formal';
        return self::curlPost($url,json_encode($data)) ;
    }


    //发送post请求
    static function curlPost($url,$data)
    {
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = FALSE; //是否返回响应头信息
        $params[CURLOPT_SSL_VERIFYPEER] = false;
        $params[CURLOPT_SSL_VERIFYHOST] = false;
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_POSTFIELDS] = $data;
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch);
        return $content;
    }


}
