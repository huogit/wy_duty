<?php


namespace App\Utilities;


/**
 * Class WYWeChatSDK
 * 网园微信应用开发工具包
 *
 * @author xuhang
 * @version 2.1.1
 * @copyright WangYuanInfo
 */
class WYWeChatSDK
{
    const API_URL_ROOT = 'https://wx-api.wangyuan.info'; // API 根目录
    const API_URL_GET_ACCESS_TOKEN = '/api/getAccessToken'; // 获取 AccessToken
    const API_URL_SEND_TEMPLATE_MESSAGE = '/api/sendTplMessage'; // 推送模板消息
    const API_URL_SEND_TAG_TEMPLATE_MESSAGE = '/api/sendTagTplMessage'; // 对标签用户推送模板消息
    const API_URL_GET_OAUTH_REDIRECT = '/api/getOAuthRedirectUrl'; // 获取授权跳转地址
    const API_URL_CHECK_SUBSCRIBED = '/api/checkSubscribed'; // 判断是否关注
    const API_URL_GET_OAUTH_INFO = '/api/getOAuthInfo'; // 通过 Code 换取授权信息
    const OAUTH_EXPIRE_URL = 'https://wx-app.wangyuan.info/expired/index.html'; // 加密授权过期页
    const ENCRYPT_PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCkSJx8qMBsug8WyoXrh4KU8kwo
GVK57iN5nqbnVAeDxclaf86uv8SQ9FaJlxz5IGEER8HJ+vjfxe4sNHRfQA0V8+Va
l8LUie76KuB9wj7zmN9TTkss55AyMBQfkv3xNp6To1bieiblsST7BeDVTfhZlqXP
r+fhLTg6IRgC6c/Y7QIDAQAB
-----END PUBLIC KEY-----';

    /**
     * @var int 返回码
     */
    protected $responseCode;

    /**
     * @var string 错误返回信息
     */
    protected $responseErrMsg;

    /**
     * @var mixed 错误返回源数据
     */
    protected $responseErrRaw;

    protected $config = [
        'tag' => [
            '网园' => 101,
            '编程部' => 100,
            '页面设计' => 102,
            '页面前端' => 103,
            '文秘部' => 104,
            '测试组' => 106,
            '内网异常通知' => 107
        ],
        'template' => [
            '准考证发放提醒' => '1FlfxyPSbL0m1h8hctqLvHpBYcUpsQibVOMV8FIfrFE',
            '服务完成通知' => 'CL9pRwa9cXHbYQggPRGRg-LgVVBn6Fx8oITEvJ1LNq4',
            '还书通知' => 'FLT7e0X69KAOgCDawjRR0EwOEUI48o7LFYbTvuZHgl0',
            '关注成功通知' => 'SQRjcPChhEC7CMunFw8omoA7746wIXb5CZVyUMOezm0',
            '系统异常提醒' => 'T176RMEcw8JNzAnP0fv7fnM4MNoMvaqWa1C1WHCOjrk',
            '活动报名成功通知' => 'Ve6DxPMUv6nkwZ79KVwhnO_mVj2Be8v6PZ7yj5dPMyE',
            '预约审核结果通知' => 'XiFDNcwZm07xB9--ex92s33Bcb7nLA6h-oCZWLMfr4E',
            '服务暂停使用通知' => 'fLny5s553QCVl_vtx0IM0q06IFjEvAtlDpeRU0QsvMI',
            '预警通知' => 'js9ODanUBSGjB7-WmqbT5ibrRorEJ-2X5cTXaG61tz8',
            '解绑成功通知' => 'htqnTFsKnkvzNkKKeMs31NMdlxTnlmydiSjMIwap7j8',
            '社团招新面试通知' => 'lk_MOa6MBINzKTJd8FuIP2dAgercETxbGAhMH1zMpj0',
            '账号绑定成功通知' => 'omKR1z7Gj4WtWEQuxZ714cwQUz3lXqpD-emN9W18AKE',
            '面试结果通知' => 'wJaVYihzzowfrnRMN8XlYLKKjJEd_5E-NoAZzlMVEVY',
            '网络异常提醒' => 'M7NVlZO7x-HeH65fTDaz6rEYaHuv7itr7GU0dPYKJ8c'
        ],
        'oauth_app' => [
            'bz', // 系统报装
            'hydropower', // 宿舍水电
            'jwc', // 教务平台
            'todolist', // 待办事项
            'test',
            'test_hydropower',
            'duty',
        ]
    ];

    /**
     * API 解包函数
     *
     * @param mixed $data
     * @return mixed|bool
     */
    protected function api_unpack($data)
    {
        $data = json_decode($data, true);
        if (!$data) {
            return false;
        }

        $this->responseCode = $data['code'];
        if ($this->responseCode == 200) {
            return isset($data['data']) ? $data['data'] : true;
        } else {
            $this->responseErrMsg = $data['errmsg'];
            $this->responseErrRaw = $data;
            return false;
        }
    }

    /**
     * 获取返回码
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * 获取错误返回源数据
     *
     * @return mixed
     */
    public function getResponseErrRaw()
    {
        return $this->responseErrRaw;
    }

    /**
     * 获取错误返回信息
     *
     * @return string
     */
    public function getResponseErrMsg()
    {
        return $this->responseErrMsg;
    }


    /**
     * 获取 AccessToken
     *
     * @return bool|mixed
     */
    public function getAccessToken()
    {
        $result = $this->http_get(self::API_URL_ROOT . self::API_URL_GET_ACCESS_TOKEN);
        return $this->api_unpack($result);
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
    public function sendTplMessage($openid, $tplMask, $data, $url = '')
    {
        if (!array_key_exists($tplMask, $this->config['template'])) {
            return false;
        }
        $tplId = $this->config['template'][$tplMask];
        $data = [
            'data' => json_encode($data),
            'url' => $url
        ];
        $result = $this->http_post(self::API_URL_ROOT . self::API_URL_SEND_TEMPLATE_MESSAGE . "/{$openid}/{$tplId}", $data);
        return $this->api_unpack($result);
    }

    /**
     * 对标签用户发送模板消息
     *
     * @param int $tagMask 标签ID
     * @param string $tplMask 模板消息ID
     * @param array $data 模板消息参数
     * @param string $url 链接地址
     * @return bool|mixed
     */
    public function sendTagTplMessage($tagMask, $tplMask, $data, $url = '')
    {
        if (!array_key_exists($tagMask, $this->config['tag'])) {
            return false;
        }
        $tagId = $this->config['tag'][$tagMask];

        if (!array_key_exists($tplMask, $this->config['template'])) {
            return false;
        }
        $tplId = $this->config['template'][$tplMask];

        $data = [
            'data' => json_encode($data),
            'url' => $url
        ];
        $result = $this->http_post(self::API_URL_ROOT . self::API_URL_SEND_TAG_TEMPLATE_MESSAGE . "/{$tagId}/{$tplId}", $data);
        return $this->api_unpack($result);
    }

    /**
     * 判断是否关注
     *
     * @param string $openid 微信用户唯一ID
     * @return bool
     */
    public function checkSubscribed($openid): bool
    {
        $result = $this->http_get(self::API_URL_ROOT . self::API_URL_CHECK_SUBSCRIBED . "/{$openid}");
        return $this->api_unpack($result);
    }

    /**
     * 通过 Code 换取授权信息
     *
     * @param string $code
     * @return bool|mixed
     */
    public function getOAuthInfo($code)
    {
        $result = $this->http_get(self::API_URL_ROOT . self::API_URL_GET_OAUTH_INFO . "/{$code}");
        return $this->api_unpack($result);
    }

    /**
     * 获取授权跳转地址
     *
     * @param string $state 应用标识
     * @param string $scope 授权方式（snsapi_base/snsapi_userinfo）
     * @return bool|mixed
     */
    public function getOAuthRedirectUrl($state, $scope = 'snsapi_userinfo')
    {
        if (!in_array($state, $this->config['oauth_app'])) {
            return false;
        }
        $result = $this->http_get(self::API_URL_ROOT . self::API_URL_GET_OAUTH_REDIRECT . "/{$state}/{$scope}");
        return $this->api_unpack($result);
    }

    /**
     * 获取 RSA 加解密对象
     *
     * @return RSAClientCrypt
     */
    public function crypt()
    {
        return new RSAClientCrypt(self::ENCRYPT_PUBLIC_KEY);
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
     * CURL 模块
     *
     * @param string $url 目的地址
     * @param string|array $postData POST数据
     * @param string $cookies 携带 Cookie
     * @param string|array $headers 自定义Header
     * @param bool $header_on Header返回
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

        //添加日志
        if (curl_errno($ch)) {
            $error = [
                "url" => $url,
                "curl_error" => curl_error($ch)
            ];
            Log::error("【WX_API请求】 ERROR : " . json_encode($error));
            return false;
        }

        curl_close($ch);
        return $data;
    }
}


/**
 * Class RSAClientCrypt
 * RSA 公匙加密类
 *
 * @author laijingwu
 */
class RSAClientCrypt
{
    /**
     * RSA 加密公匙对象
     *
     * @var resource
     */
    protected $pkey_public;

    /**
     * RSACrypt constructor.
     *
     * @param string $public_key RSA 加密公匙
     * @throws \Exception
     */
    function __construct($public_key)
    {
        if (!extension_loaded('openssl'))
            throw new \Exception("Require openssl extension");

        $public_key = openssl_pkey_get_public($public_key);
        if (!$public_key)
            throw new \Exception("Invalid public key");

        $this->pkey_public = $public_key;
    }

    /**
     * RSA 加密（Url 安全）
     *
     * @param mixed $object 原文
     * @return bool|string 密文
     */
    public function urlSafeEncrypt($object)
    {
        $encode = $this->encrypt($object);
        if (is_string($encode))
            return str_replace(['+', '/', '='], ['-', '_', ''], $encode);
        else
            return $encode;
    }

    /**
     * RSA 解密（Url 安全）
     *
     * @param string $string 密文
     * @return bool|mixed 原文
     */
    public function urlSafeDecrypt($string)
    {
        $encode = str_replace(['-', '_'], ['+', '/'], $string);
        $mod4 = strlen($encode) % 4;
        if ($mod4) {
            $encode .= substr('====', $mod4);
        }
        return $this->decrypt($encode);
    }

    /**
     * RSA 加密
     *
     * @param mixed $object 原文
     * @return bool|string 密文
     */
    public function encrypt($object)
    {
        $data = json_encode($object);
        $encode_data = null;
        $split = str_split($data, 110); // 1024bit && OPENSSL_PKCS1_PADDING  不大于 117 即可
        foreach ($split as $part) {
            $isOkay = openssl_public_encrypt($part, $en_data, $this->pkey_public);
            if (!$isOkay) {
                return false;
            }
            $encode_data .= base64_encode($en_data);
        }
        return $encode_data;
    }

    /**
     * RSA 解密
     *
     * @param string $encode_data 密文
     * @return bool|mixed 原文
     */
    public function decrypt($encode_data)
    {
        $decode_data = null;
        $split = str_split($encode_data, 172); // 1024bit  固定172
        foreach ($split as $part) {
            $isOkay = openssl_public_decrypt(base64_decode($part), $de_data, $this->pkey_public); // 172 字节一组 base64_encode
            if (!$isOkay) {
                return false;
            }
            $decode_data .= $de_data;
        }
        return json_decode($decode_data, true);
    }
}
