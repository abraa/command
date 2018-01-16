<?php
/**
 * 微信公众平台 消息接口
 * 流程：1、当用户回复公众平台时，消息传到本地址。2、本地址程序可回复消息给用户。
 * 微信服务器在五秒内收不到响应会断掉连接。
 * http://mp.weixin.qq.com/wiki/
 *
 * @date   2013-05-06
 */

namespace extend;

class Wechat
{
    public static $token = NULL; // 公众平台填写的token
    public static $app_id = NULL; // 公众平台的app_id
    public static $app_secret = NULL; // 公众平台的app_secret
    public static $call_url = NULL; //回调地址
    public static $access_token = '';
    public static $userOpenId, $adminOpenId;
    private static $userInfo;
    private static $data;               //postData数据
    const QR_SCENE = 'QR_SCENE';        //临时二维码scene_id
    const QR_STR_SCENE = 'QR_STR_SCENE'; //临时二维码scene_str
    const QR_LIMIT_SCENE = 'QR_LIMIT_SCENE'; //永久二维码scene_id
    const QR_LIMIT_STR_SCENE = 'QR_LIMIT_STR_SCENE'; //永久二维码scene_str

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
     * 获得普通用户发送过来的消息
     * 当普通微信用户向公众账号发消息时，微信服务器将POST该消息到填写的URL上。
     *
     * @return bool|\SimpleXMLElement
     */
    public static function postData()
    {
        $postStr = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");//这里在php7下不能获取数据，使用 php://input 代替
        if (empty($postStr)) return array();
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
		
        // 公共
        $postData['adminOpenId'] = self::$adminOpenId = strval($postObj->ToUserName);
        $postData['userOpenId'] = self::$userOpenId = strval($postObj->FromUserName); // 普通用户（一个OpenID）
        $postData['MsgId'] = strval($postObj->MsgId); // 消息id，64位整型
        $postData['MsgType'] = strval($postObj->MsgType); // text image location link event
        $postData['CreateTime'] = strval($postObj->CreateTime);

        // 文本消息 text
        $postData['Content'] = trim(strval($postObj->Content)); // 文本消息内容
        // 图片消息 image
        $postData['PicUrl'] = strval($postObj->PicUrl); // 图片链接
		//语音信息 voice
        $postData['MediaId'] = strval($postObj->MediaId); // 多媒体ID
		$postData['Recognition'] = isset($postObj->Recognition) ? trim($postObj->Recognition) : ''; // 多媒体ID

        // 地理位置消息 location
        $postData['Location_X'] = strval($postObj->Location_X); // 地理位置纬度
        $postData['Location_Y'] = strval($postObj->Location_Y); // 地理位置经度
        $postData['Scale'] = strval($postObj->Scale); // 地图缩放大小
        $postData['Label'] = strval($postObj->Label); // 地理位置信息
        // event事件的 地理位置消息LOCATION
        $postData['Latitude'] = strval($postObj->Latitude); // 地理位置纬度
        $postData['Longitude'] = strval($postObj->Longitude); // 地理位置经度
        $postData['Precision'] = strval($postObj->Precision); // 地理位置精度
        // 链接消息 link
        $postData['Title'] = strval($postObj->Title); // 消息标题
        $postData['Description'] = strval($postObj->Description); // 消息描述
        $postData['Url'] = strval($postObj->Url); // 消息链接
        // 事件推送 event
        $postData['Event'] = strval($postObj->Event); // 事件类型，subscribe(订阅)、unsubscribe(取消订阅)、CLICK(自定义菜单点击事件) card_pass_check(卡券通过审核)、card_not_pass_check（卡券未通过审核） user_get_card(用户领取卡券) user_del_card(用户删除卡券)
        $postData['EventKey'] = strval($postObj->EventKey); // 事件KEY值，与自定义菜单接口中KEY值对应

        //卡卷事件推送之审核事件
        //事件类型， card_pass_check(卡券通过审核)、card_not_pass_check（卡券未通过审核）
        $postData['ToUserName'] = strval($postObj->ToUserName); // 开发者微信号
        $postData['FromUserName'] = strval($postObj->FromUserName); //发送方open_id
        $postData['CardId'] = strval($postObj->CardId); //卡卷ID

        //卡卷领取
        //事件类型，user_get_card(用户领取卡券)
        $postData['FriendUserName'] = strval($postObj->FriendUserName); //赠送方账号（一个OpenID），"IsGiveByFriend”为1 时填写该参数。
        $postData['IsGiveByFriend'] = strval($postObj->IsGiveByFriend); //是否为转赠，1 代表是，0 代表否。
        $postData['UserCardCode'] = strval($postObj->UserCardCode); //code 序列号。自定义code 及非自定义code的卡券被领取后都支持事件推送。
        $postData['OuterId'] = strval($postObj->OuterId); //领取场景值，用于领取渠道数据统计。可在生成二维码接口及添加JS API 接口中自定义该字段的整型值。


        //删除卡卷(用户删除code)
        //事件类型，user_del_card(用户删除卡券)
        $postData['UserCardCode'] = strval($postObj->UserCardCode); //商户自定义code 值。非自定code 推送为空

        //事件类型 ,扫描带参数二维码事件
        $postData['Ticket'] = strval($postObj->Ticket);

        self::$data = $postData;                                    //类里面存一份
        return self::$data;
    }

    /**
     * 获取微信推送数据
     * @param string $name
     * @return array|mixed
     */
    public static function getData($name = '')
    {
        if(empty(self::$data)){
            $postStr = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");//这里在php7下不能获取数据，使用 php://input 代替
            if (empty($postStr)) return null;
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            if(false === $postObj){
                return null;
            }
            $postObj = (array)$postObj;
            foreach($postObj as &$item){
                $item = strval($item);                              //全部转字符串  防止微信传的参数为<![CDATA[]]>时值为: SimpleXMLElement空对象
            }
            self::$adminOpenId = isset($postObj['ToUserName']) ? $postObj['ToUserName'] : self::$adminOpenId;
            self::$userOpenId =  isset($postObj['FromUserName']) ? $postObj['FromUserName'] : self::$userOpenId;           // 普通用户（一个OpenID）
            self::$data = $postObj;
        }

        return empty($name) ? self::$data : (isset(self::$data[$name]) ? self::$data[$name]: null);
    }
    /**
     * 回复客服文本消息
     * @param $content
     * @return bool|mixed
     */
    public static function serviceText($content)
    {
        if (empty(self::$userOpenId)) return false;
        $data = '{"touser": "' . self::$userOpenId . '", "msgtype": "text",  "text": { "content": "' . $content . '" }}';
        $ret = Curl::post('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . self::getAccessToken('serviceText'), $data,false);
        $_data = json_decode($ret, true);
        return $_data;
    }

    /**
     * 回复文本消息模板
     * @param $content 长度不超过2048字节
     * @return string
     */
    public static function textTpl($content)
    {
        $textTpl = '<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    </xml>';
        return sprintf($textTpl, self::$userOpenId, self::$adminOpenId, time(), 'text', $content);
    }



    /**
     * 获取access_token
     * @param 缓存名称|string $cache_name 缓存名称，每个功能独立名称，不传不缓存  --  Add By Lemonice
     * @return string
     */
    public static function getAccessToken($cache_name = '')
    {
        if(empty(self::$access_token)){
            $accessToken = self::accessToken($cache_name);
        }else{
			$accessToken = self::$access_token;
		}
        return $accessToken;
    }

    /**
     * 请求access_token
     * @param  $cache_name ，缓存名称 每个功能独立名称，不传不缓存  --  Add By Lemonice
     * @return string
     */
    private static function accessToken($cache_name="") {

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
     * 记录文件日志
     * @param $filename
     * @param $content
     */
    public static function write_file($filename,$content,$wtype="w"){
        $fp = fopen($filename, $wtype);
        fwrite($fp, urldecode($content));
        fclose($fp);
    }



    /**
     * 获取二维码
     *
     * @param  number|string $scene_id 标识值参数
     * @param string $action_name  QR_SCENE|QR_STR_SCENE|QR_LIMIT_SCENE|QR_LIMIT_STR_SCENE
     * @param int $expire_seconds  有效时间 秒
     * @return string 返回图片地址
     */
    public static function getQrcode($scene_id,$action_name,$expire_seconds=1800)
    {
        $data = array();
        switch($action_name){
            case self::QR_SCENE:
                $data['action_name'] = 'QR_SCENE';
                $data['action_info'] = array('scene' => array('scene_id' => $scene_id));
                $data['expire_seconds'] = $expire_seconds; // 有效时间 秒  最大1800
                break;
            case self::QR_STR_SCENE:
                $data['action_name'] = 'QR_STR_SCENE';
                $data['action_info'] = array('scene' => array('scene_str' => $scene_id));
                $data['expire_seconds'] = $expire_seconds; // 有效时间 秒  最大1800
                break;
            case self::QR_LIMIT_SCENE:
                $data['action_name'] = 'QR_LIMIT_SCENE';
                $data['action_info'] = array('scene' => array('scene_id' => $scene_id));
                break;
            case self::QR_LIMIT_STR_SCENE:
                $data['action_name'] = 'QR_LIMIT_STR_SCENE';
                $data['action_info'] = array('scene' => array('scene_str' => $scene_id));
                break;
            default:
                return '';
        }
        $access_token = self::getAccessToken('getQrcode');

        $ret = Curl::post('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token, json_encode($data),false);

        $_data = json_decode($ret, true);

        if (isset($_data['errcode']) && $_data['errcode'] <> 0){
            return '';
        }else{
            return 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' .(isset($_data['ticket']) ? $_data['ticket'] : '');
        }

    }

    /**
     * 获取临时二维码
     *
     * @param number|string $scene_id 标识值
     * @param string $action_name QR_SCENE|QR_STR_SCENE
     * @param int $expire_seconds
     * @return String 返回图片地址
     */
    public static function getTempQrcode($scene_id,$action_name,$expire_seconds=1800)
    {
      return  self::getQrcode($scene_id,$action_name,$expire_seconds);
    }

    /**
     * 获取个人信息
     * @param null $openid
     * @return array|mixed
     */
    public static function getUserInfo($openid = NULL)
    {
       if(!isset(self::$userInfo)){
           $ret = Curl::get('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . self::getAccessToken('getUserInfo') . '&openid=' . (!is_null($openid) ? $openid : self::$userOpenId) . '&lang=zh_CN');
           $ret = json_decode($ret, true);
           self::$userInfo = (isset($ret['errcode']) && $ret['errcode'] <> 0) ? []  : $ret;
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
        $ret = Curl::post('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . self::getAccessToken('sendTemplate'), $data);
        $_data = json_decode($ret, true);
        return (isset($_data['errcode']) && $_data['errcode']==0);
    }

    /**
     * 回复客服图文消息
     */
    public static function serviceNews($article_array) {
        if (empty(self::$userOpenId) || !is_array($article_array) || empty($article_array)) return '';
        if (!is_array(current($article_array))) $article_array = array($article_array);
        $articles = '';
        foreach ($article_array as $val) {
            $articles .= ' {
					 "title":"' . (isset($val['title']) ? $val['title'] : '') . '",
					 "description":"' .  (isset($val['description']) ? $val['description'] : '') . '",
					 "url":"' .  (isset($val['url']) ? $val['url'] : '') . '",
					 "picurl":"' . (isset($val['picurl']) ? $val['picurl'] : '') . '"
				 },';
        }
        $articles = substr($articles, 0, -1);
        $tpl = '{
			"touser":"' . self::$userOpenId . '",
			"msgtype":"news",
			"news":{
				"articles": [
					' . $articles . '
				 ]
			}
		}';

        $ret = Curl::post('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . self::getAccessToken('serviceNews'), $tpl);
        $_data = json_decode($ret, true);
        return (!isset($_data['errcode'])|| $_data['errcode'] == 0);
    }

    /**
     * // 创建菜单
     * @param array $data
     * @return mixed
     */
    public static function createMenu($data = array())
    {
        $jsonData = json_encode($data);
        $jsonData = self::_unicodeDecode($jsonData);
        $ret = Curl::post('https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . self::getAccessToken('createMenu'), $jsonData);
        $_data = json_decode($ret, true);
        return $_data;
    }

    /**
     * // 删除菜单
     * @return mixed
     */
    public static function removeMenu(){
        $ret = Curl::post('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . self::getAccessToken('removeMenu'),array());
        $_data = json_decode($ret, true);
        return $_data;
    }

    /**
     * // unicode \u形式的中文 转成 普通中文
     * @param $string
     * @return mixed
     */
    private static function _unicodeDecode($string)
    {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', create_function(
            '$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
        ), $string);
    }

    /**
     *  作用：生成可以获得code的url
     * @param string $scope
     * @param string $redirect_uri
     * @param string $state
     * @return string
     */
    public static function createOauthUrlForCode($scope = "snsapi_base",$redirect_uri="",$state="base")
    {
        $urlObj = array();
        if(!$redirect_uri) $redirect_uri = self::$call_url;
        $urlObj["appid"] = self::$app_id;
        $urlObj["redirect_uri"] = $redirect_uri;
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = $scope;
        $urlObj["state"] = $state."#wechat_redirect";
        $bizString =self::formatBizQueryParaMap($urlObj, false);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }


    /**
     *  作用：生成可以获得openid的url
     */
    public static function createOauthUrlForOpenid($code)
    {
        $urlObj = array();
        $urlObj["appid"] = self::$app_id;
        $urlObj["secret"] = self::$app_secret;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = self::formatBizQueryParaMap($urlObj, false);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }


    /**
     *  作用：格式化参数，签名过程需要使用
     */
    public static function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar="";
        if (strlen($buff) > 0)
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }
}



