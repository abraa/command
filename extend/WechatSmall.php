<?php
/**
 * 微信小程序接口
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/28
 * Time: 20:36
 */


namespace extend;

class WechatSmall
{
    public static $token = NULL; // 公众平台填写的token
    public static $app_id = NULL; // 公众平台的app_id
    public static $app_secret = NULL; // 公众平台的app_secret
    public static $call_url = NULL; //回调地址
    public static $access_token = '';
    public static $sessionKey = '';
    public static $userOpenId, $adminOpenId;
    private static $userInfo;
    private static $data;               //postData数据
    private static $errCodeHandle = true;                 //是否进行errCode预处理
    private static $errCodeHandleList = [];               //不进行errCode预处理的errcode列表



    /**
     * 初始化赋值 token,app_id,app_secret
     * @param array $params
     */
    public static function init($params=array()){
        self::$token = isset($params['token']) ? $params['token'] : self::$token;
        self::$app_id = isset($params['app_id']) ? $params['app_id'] : self::$app_id;
        self::$app_secret = isset($params['app_secret']) ? $params['app_secret'] : self::$app_secret;
    }
    /**
     * 加密后的字符串与$signature对比，标识该请求来源于微信
     * @return bool
     */
    public static function checkSignature()
    {
        $signature = isset($_GET['signature'])?$_GET['signature']:null;
        $timestamp = isset($_GET['timestamp'])?$_GET['timestamp']:null;
        $nonce =  isset($_GET['nonce'])?$_GET['nonce']:null;
        $tmpArr = array(self::$token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据code获取 session_key
     *  "openid": "OPENID",
    "session_key": "SESSIONKEY",
    "unionid": "UNIONID"
     * @param $code
     * @return bool|mixed
     */
    public static function authorizationCode($code){
        if (empty(self::$app_id)) return false;
        $ret = Curl::get('https://api.weixin.qq.com/sns/jscode2session?appid='.self::$app_id.'&secret='.self::$app_secret.'&js_code='.$code.'&grant_type=authorization_code');
        $_data = json_decode($ret, true);
        return isset($_data['errcode']) ? false : $_data;

    }


    /**
     * $encryptedData解密
     * @param $encryptedData
     * @param $iv
     * @return bool|mixed
     */
    public static function decryptData($encryptedData, $iv)
    {
        if (strlen(self::$sessionKey) != 24) {
            return false;
        }
        $aesKey=base64_decode(self::$sessionKey);


        if (strlen($iv) != 24) {
            return false;
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode( $result ,true);
        if( $dataObj  == NULL )
        {
            return false;
        }
        if( $dataObj['watermark']['appid'] != self::$app_id )
        {
            return false;
        }
        return $dataObj;
    }

    /**
     * 获取access_token
     * @param bool $cache       是否重新生成缓存
     * @return string
     */
    public static function getAccessToken($cache = false)
    {
        $cacheName = 'accessToken';             //缓存文件名称
        if($cache){             //重新生成缓存
            self::$access_token = self::accessToken();
            if(empty( self::$access_token)){
                return false;
            }
            file_put_contents($cacheName,self::$access_token);
        }else{
            if(!file_exists($cacheName) || 7200 <= time() - filemtime($cacheName)){         //文件不存在或者已经超时 重新生成
                return self::getAccessToken(true);
            }
            if(empty(self::$access_token)){
                self::$access_token = file_get_contents($cacheName);
            }
        }
        return self::$access_token;
    }

    /**
     * 请求access_token
     * @return string
     */
    private static function accessToken() {

        if(!isset($data['access_token'])){
            //获取token
            $ret = Curl::get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . self::$app_id . '&secret='.self::$app_secret);
            if($ret != ''){
                $data = json_decode($ret, true);
            }
        }
        return isset($data['access_token']) ? $data['access_token'] : NULL;
    }

    /**
     * 获取个人信息
     * @param null $openid
     * @return array|mixed
     */
    public static function getUserInfo($openid = NULL)
    {
        if(!isset(self::$userInfo)){
            $ret = Curl::get('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . self::getAccessToken() . '&openid=' . (!is_null($openid) ? $openid : self::$userOpenId) . '&lang=zh_CN');
            $ret = json_decode($ret, true);
            if(isset($ret['errcode'])){
                $ret =  self::errCodeHandle($ret['errcode']);
                if(empty($ret)) self::$userInfo = array();
            }else{
                self::$userInfo = $ret;
            }
        }
        return self::$userInfo;
    }

    /**
     * 发送模版消息
     * @param $template_id string 模版ID
     * @param $data_array array data参数
     * @param $url string 点击模版消息后跳转的链接，如果传空，则苹果点击跳转空白页、安卓没反映
     * @param $topcolor string 头文字的颜色
     * @return string
     */
    public static function sendTemplate($template_id, $data_array = array(), $url = '', $topcolor = '#FF0000'){
        if(empty($data_array) || $template_id == ''){
            return false;
        }
        $data = '{
			"touser":"'.self::$userOpenId.'",
			"template_id":"'.$template_id.'",
			"url":"'.$url.'",
			"topcolor":"'.$topcolor.'",
			"data":{';
        $data_count = count($data_array);
        $i = 0;
        foreach($data_array as $key=>$value){
            $data .= '"'.$key.'":{
						"value":"'.(isset($value['value']) ? $value['value'] : '').'",
						"color":"'.(isset($value['color']) && $value['color']!='' ? $value['color'] : '#000000').'"
					}';
            if($i < $data_count-1){
                $data .= ",";
            }
            $i++;
        }
        $data .= '}
		}';
        $ret = Curl::post('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . self::getAccessToken(), $data);
        $_data = json_decode($ret, true);
        if(isset($_data['errcode'])){
            return self::errCodeHandle($_data['errcode']);
        }
        return true;
    }


    /**
     * 获取微信小程序二维码    100 000 限制
     * @param $path
     * @param int $width
     * @param bool $auto_color
     * @param $line_color  {"r":"0","g":"0","b":"0"}
     * @return mixed
     */
    public static function getwxacode($path,$width=430,$auto_color=false,$line_color	= array()){
        $data = array(
            'path' => $path,
            'width' => $width,
            'auto_color' => $auto_color,
            'line_color' => $line_color,
        );
        $url = Curl::post('https://api.weixin.qq.com/wxa/getwxacode?access_token=' . self::getAccessToken(), $data);
        if(isset($url['errcode'])){
            return self::errCodeHandle($url['errcode']);
        }
        return $url;
    }

    /**
     * @param $scene        scene 的参数值需要进行 urlencode
     * @param $path
     * @param int $width
     * @param bool $auto_color
     * @param array $line_color
     * @return bool|mixed
     */
    public static function getwxacodeunlimit($scene,$path,$width=430,$auto_color=false,$line_color	= array()){
        $data = array(
            'scene' => $scene,
            'path' => $path,
            'width' => $width,
            'auto_color' => $auto_color,
            'line_color' => $line_color,
        );
        $url = Curl::post('https://api.weixin.qq.com/wxa/getwxacode?access_token=' . self::getAccessToken(), $data);
        if(isset($url['errcode'])){
            return self::errCodeHandle($url['errcode']);
        }
        return $url;
    }

    /**
     * Wechat错误逻辑预处理
     */
    public static function errCodeHandle($errcode){
        if($errcode == 0) return true;                                  //0成功
        if(false === self::$errCodeHandle || in_array($errcode,self::$errCodeHandleList)){
            return false;
        }
        switch($errcode){
            case 40001:                 //获取access_token时Secret错误，或者access_token无效
            case 40014:                 //不合法的access_token
            case 41001:                 //缺少access_token参数
            case 42001:                 //access_token过期
                self::getAccessToken(true);             //重新获取一次
                self::$errCodeHandleList = array_merge(self::$errCodeHandleList,array(40001,40014,41001,42001));            //如果再出现不做处理
                $result = debug_backtrace(false,2);
                $result = $result[1];
                return call_user_func_array(array(self::class,$result[1]['function']),$result[1]['args']);
                break;
        }
        return false;
    }
}



