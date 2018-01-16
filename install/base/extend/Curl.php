<?php
/**
 * ====================================
 * Curl类库
 * ====================================
 * Author: 9004396
 * Date: 2017-11-08 11:51
 * ====================================
 * Project: ggzy
 * File: Curl.php
 * ====================================
 */

namespace extend;
class Curl
{
    public static $is_proxy = true; // 是否启用代理
    public static $proxy_ip = ''; // 234.234.234.234代理服务器地址
    public static $cookie_file;
    public static $user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; SeaPort/1.2; Windows NT 5.2; .NET CLR 1.1.4322)';
    public static $compression = 'gzip';
    public static $timeout = 30;

    //证书相关
    static $certtype_cert_name = NULL;
    static $certtype_cert_file = NULL;
    static $certtype_key_name = NULL;
    static $certtype_key_file = NULL;

    /**
     * 模拟GET方式获取
     * @param $url 请求链接
     * @return mixed
     */
    public static function get($url)
    {
        $curl = curl_init();
        if (self::$is_proxy) {
            curl_setopt($curl, CURLOPT_PROXY, self::$proxy_ip);
        }
        if (self::$cookie_file) {
            curl_setopt($curl, CURLOPT_COOKIEFILE, self::$cookie_file); // 读取上面所储存的Cookie信息
        }
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查在
        curl_setopt($curl, CURLOPT_USERAGENT, self::$user_agent); // 模拟用户使用的浏览器
        @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_HTTPGET, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_TIMEOUT, self::$timeout); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $res = curl_exec($curl);
        if (curl_errno($curl)) {
            //echo 'Error:' . curl_error($curl);
        }
        curl_close($curl);
        return $res;
    }


    /**
     * 模拟POST方式获取
     * @param string $url 请求连接
     * @param array|string $data 请求参数
     * @return mixed
     */
    public static function post($url, $data = [])
    {
        $curl = curl_init();
        if (self::$is_proxy) {
            curl_setopt($curl, CURLOPT_PROXY, self::$proxy_ip);
        }
        if (self::$cookie_file) {
            curl_setopt($curl, CURLOPT_COOKIEFILE, self::$cookie_file); // 读取上面所储存的Cookie信息
        }
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_USERAGENT, self::$user_agent); // 模拟用户使用的浏览器
        @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, self::$timeout); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $res = curl_exec($curl);
        if (curl_errno($curl)) {
            //echo 'Error:' . curl_error($curl);
        }
        curl_close($curl);
        return $res;
    }

    /**
     * 以post方式提交xml到对应的接口url
     * @param string $xml  需要post的xml数据
     * @param string $url  URL地址
     * @return bool|mixed
     */
    static public function postXml($xml, $url) {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_USERAGENT, self::$user_agent); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::$user_agent);
        curl_setopt($ch, CURLOPT_ENCODING, self::$compression);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if(self::$proxy_ip) curl_setopt($ch,CURLOPT_PROXY, self::$proxy_ip);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        if(!empty(self::$certtype_cert_name) && !empty(self::$certtype_cert_file)){
            curl_setopt($ch,CURLOPT_SSLCERTTYPE, self::$certtype_cert_name);
            curl_setopt($ch,CURLOPT_SSLCERT, self::$certtype_cert_file);
        }
        if(!empty(self::$certtype_key_name) && !empty(self::$certtype_key_file)){
            curl_setopt($ch,CURLOPT_SSLKEYTYPE, self::$certtype_key_name);
            curl_setopt($ch,CURLOPT_SSLKEY, self::$certtype_key_file);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 模拟获取
     * @param string $url   请求链接
     * @param array|string $data   参数
     * @param string $method 请求类型
     * @return mixed
     */
    public static function request($url, $data, $method = 'POST')
    {
        if (strtolower($method) == 'post') {
            $result = self::post($url, $data);
        } else {
            if (is_array($data)) {
                $url .= http_build_query($data,'','&');
            } else {
                $url .= $data;
            }
            $result = self::get($url);
        }
        return $result;
    }

}