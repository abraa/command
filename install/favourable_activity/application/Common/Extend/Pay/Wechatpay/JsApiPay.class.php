<?php
/**
 * ====================================
 * JSAPI支付实现类
 * 该类实现了从微信公众平台获取code、通过code获取openid和access_token、
 * 生成jsapi支付js接口所需的参数、生成获取共享收货地址所需的参数
 *
 * 该类是微信支付提供的样例程序，商户可根据自己的需求修改，或者使用lib中的api自行开发
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-27 10:06
 * ====================================
 * File: JsApiPay.class.php
 * ====================================
 */
namespace Common\Extend\Pay\Wechatpay;


class JsApiPay
{
	/**
	 * 
	 * 网页授权接口微信服务器返回的数据，返回样例如下
	 * {
	 *  "access_token":"ACCESS_TOKEN",
	 *  "expires_in":7200,
	 *  "refresh_token":"REFRESH_TOKEN",
	 *  "openid":"OPENID",
	 *  "scope":"SCOPE",
	 *  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
	 * }
	 * 其中access_token可用于获取共享收货地址
	 * openid是微信支付jsapi支付接口必须的参数
	 * @var array
	 */
	public $data = null;

    public $appId = NULL;
    public $appSecret = NULL;
    public $apiCallUrl = NULL;
	
	/**
	 * 
	 * 通过跳转获取用户的openid，跳转流程如下：
	 * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
	 * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
	 * 
	 * @return 用户的openid
	 */
	public function getOpenid($code = '') {
        $code = empty($code) ? $_GET['code'] : $code;
		//通过code获得openid
		if (empty($code)){
			$url = $this->createOauthUrlForCode();
			header("Location: $url");
			exit();
		} else {
			//获取code码，以获取openid
			$openid = $this->getOpenidFromMp($code);
			return $openid;
		}
	}
	
	/**
	 * 
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @throws WxPayException
	 * 
	 * @return json数据，可直接填入js函数作为参数
	 */
	public function getJsApiParameters($UnifiedOrderResult)
	{
		if(!array_key_exists("appid", $UnifiedOrderResult)
		|| !array_key_exists("prepay_id", $UnifiedOrderResult)
		|| $UnifiedOrderResult['prepay_id'] == "")
		{
            die("参数错误");
		}
        $WxPay = new \Common\Extend\Pay\Wechatpay\Library\WxPay();

		$jsapi = new WxPayJsApiPay();
		$jsapi->SetAppid($UnifiedOrderResult["appid"]);
		$timeStamp = time();
		$jsapi->SetTimeStamp("$timeStamp");
		$jsapi->SetNonceStr($WxPay->getNonceStr());
		$jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);
		$jsapi->SetSignType("MD5");
		$jsapi->SetPaySign($jsapi->MakeSign());
		$parameters = json_encode($jsapi->GetValues());
		return $parameters;
	}
	
	/**
	 * 
	 * 通过code从工作平台获取openid机器access_token
	 * @param string $code 微信跳转回来带上的code
	 * 
	 * @return openid
	 */
	public function getOpenidFromMp($code) {
		$url = $this->createOauthUrlForOpenid($code);
        $res = \Common\Extend\Curl::get($url);
		//取出openid
		$data = json_decode($res,true);
		$this->data = $data;
		$openid = $data['openid'];
		return $openid;
	}
	
	/**
	 * 
	 * 拼接签名字符串
	 * @param array $urlObj
	 * 
	 * @return 返回已经拼接好的字符串
	 */
	private function toUrlParams($urlObj) {
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			if($k != "sign"){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
	
	/**
	 * 
	 * 获取地址js参数
	 * 
	 * @return 获取共享收货地址js函数需要的参数，json格式可以直接做参数使用
	 */
	public function getEditAddressParameters() {
		$getData = $this->data;
		$data = array();
		$data["appid"] = $this->appId;
		$data["url"] = $this->apiCallUrl;
		$time = time();
		$data["timestamp"] = "$time";
		$data["noncestr"] = "1234568";
		$data["accesstoken"] = $getData["access_token"];
		ksort($data);
		$params = $this->toUrlParams($data);
		$addrSign = sha1($params);
		
		$afterData = array(
			"addrSign" => $addrSign,
			"signType" => "sha1",
			"scope" => "jsapi_address",
			"appId" => WECHAT_APPID,
			"timeStamp" => $data["timestamp"],
			"nonceStr" => $data["noncestr"]
		);
		$parameters = json_encode($afterData);
		return $parameters;
	}
	
	/**
	 * 
	 * 构造获取code的url连接
	 * 
	 * @return 返回构造好的url
	 */
	public function createOauthUrlForCode($scope = "snsapi_base",$host = '') {
        $redirectUrl = $this->apiCallUrl;
        if(!empty($host)){
            if(strpos($redirectUrl,'?')){
                $redirectUrl .= '&';
            }else{
                $redirectUrl .= '?';
            }
            $redirectUrl .= 'host='.$host;
        }
		$urlObj["appid"] = $this->appId;
		$urlObj["redirect_uri"] = "$redirectUrl";
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = $scope;
		$urlObj["state"] = "STATE"."#wechat_redirect";

		$bizString = $this->toUrlParams($urlObj);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}
	
	/**
	 * 
	 * 构造获取open和access_toke的url地址
	 * @param string $code，微信跳转带回的code
	 * 
	 * @return 请求的url
	 */
    public function createOauthUrlForOpenid($code) {
		$urlObj["appid"] = $this->appId;
		$urlObj["secret"] = $this->appSecret;
		$urlObj["code"] = $code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->toUrlParams($urlObj);
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
	}
}