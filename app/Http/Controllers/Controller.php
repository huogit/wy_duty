<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $config = [
        'template' => [
            '审核提醒' => 'NneweDTttwI6OdpvMFY9icYPtLBaiGNRib0ejiPcgVk',
            '审核结果通知' => '5L5HdpAsFPXRfJvBOWW0xlMZVSggQBk1nt6IpZ3r_pQ',
            '考勤提醒' => 'lrfJHjJAZrKINe43q1mRc0XSdejxh0ql5kFpybZixgE'
        ],
    ];

    public function response($code,$message,$data=null)
    {
        if (is_null($data))
            $content = compact('code','message');
        else
            $content = compact('code','message','data');

        return json_encode($content);
    }

    public function nowWeek()
    {
        $school_begin_date = env('SCHOOL_BEGIN_DATE');// 开学日期
        $difference = time() - strtotime($school_begin_date); // 今天 - 开学日期
        return (int)ceil($difference / 604800); // 第几周
    }

    public function nowDay()
    {
        return (int)date('w'); // 星期几
    }

    public function nowTime()
    {
        $difference = time() - strtotime(date('Y-m-d')); // 现在时间 - 今天0点
        if ($difference >= 52500 && $difference <= 59100) // 2.35-4.25 56节
            $time = 0;
        elseif ($difference > 59100 && $difference <= 65100) // 4.25-6.05 78节
            $time = 1;
        elseif ($difference > 65100)  // 78节以后
            $time = 2;
        else
            $time = -1;  // 56节以前

        return $time;
    }

    // 换算值班日期
    public function duty_date($week,$day)
    {
        // 比如当前为第一周，不应该加一周，应该加0周
        $week--;
        // $day--;
        return date('Y-m-d',strtotime(env('SCHOOL_BEGIN_DATE')." + {$week}week {$day}day"));
    }

    public function duty_dateTime($week,$day,$time)
    {
        $str = $time == 0 ? ' 14:40:00' : ' 16:30';
        return $this->duty_date($week,$day).$str;
    }

    // 随机颜色 // TODO：改成从一些能用的颜色中选择
    public function randrgb() 
    { 
      $str='0123456789ABCDEF'; 
        $estr='#'; 
        $len=strlen($str); 
        for($i=1;$i<=6;$i++) 
        { 
            $num=rand(0,$len-1);   
            $estr=$estr.$str[$num];  
        } 
        return $estr; 
    }

    /**
     * HTTP GET请求
     *
     * @param string $url 目的地址
     * @return string
     */
    public function http_get($url)
    {
        return $this->http_requests($url);
    }

    /**
     * HTTP POST请求
     *
     * @param string $url 目的地址
     * @param string|array $postData POST数据
     * @return string
     */
    public function http_post($url, $postData)
    {
        return $this->http_requests($url, $postData);
    }

    /**
     * HTTP 请求
     *
     * @param string $url 目的地址
     * @param string|array $postData POST数据
     * @return string
     */
    public function http_requests($url, $postData = '', $cookies = '', $headers = '', $header_on = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); // 请求地址
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 不直接打印
        curl_setopt($ch, CURLOPT_HEADER, $header_on); // Header返回
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // HTTPS请求 不验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不检查是否有证书加密算法

        if ($postData) { // 开启POST提交
            if (is_array($postData)) $postData = http_build_query($postData);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        if ($cookies) // 携带Cookie
            curl_setopt($ch, CURLOPT_COOKIE, $cookies);

        if ($headers) // 自定义Header
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 推送模板消息
     *
     * @param string $openid 微信用户唯一ID
     * @param string $tplMask 模板消息标识
     * @param array $data 模板消息参数
     * @param string $url 链接地址
     * @return bool|mixed
     */
    public function sendTplMessage($openid, $tplMask, $data, $pagepath = '')
    {
        if (!array_key_exists($tplMask, $this->config['template'])) {
            return false;
        }

        $miniprogram = [
            "appid" => "wx94d6029e19a3e7c9",
            "pagepath" => $pagepath
        ];
        $tplId = $this->config['template'][$tplMask];
        $postData = [
            "data" => json_encode($data),
            "miniprogram" => $miniprogram,
        ];
        $url = "https://wx-api.wangyuan.info/api/sendTplMessage/{$openid}/{$tplId}";
        $result = $this->http_post($url, $postData);
        return $this->response(200,'ok',$result);
    }

    /**
     * 当前时间是否超过传入参数转换的时间
     *
     * @param $week
     * @param $day
     * @param $time
     * @return bool
     */
    public function isPastDue($week,$day,$time)
    {
        if ($this->duty_date($week, $day) < date('Y-m-d')) // 值班日期 < 当前日期
        {
            return true;
        } elseif ($this->duty_date($week, $day) == date('Y-m-d')) {
            $His = date('H:i:s');
            if ($time == 0 && $His > '16:25')
                return true;
            if ($time == 1 && $His > '18:05')
                return true;
        }

        return false;
    }

}
