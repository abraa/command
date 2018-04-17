<?php
/**
 * ====================================
 * 微信
 * ====================================
 * Author: 9009221
 * Date: 2016-07-25
 * ====================================
 * File: WechatController.class.php
 * ====================================
 */
namespace Home\Controller;
use \Common\Extend\Wechat;
use Common\Controller\InitController;
use \Common\Logic\Wechat\WechatInit;

class WechatController extends InitController
{
    /**
     * 公众号ID - 数据库里面的ID
     * @var int
     */
    private $account_id = 3;
    /**
     * 微信业务逻辑层
     * @var null
     */
    private $logicWechat = NULL;

    public function __construct()
    {
        parent::__construct();

        Wechat::$app_id = C('appid');
        Wechat::$app_secret = C('appsecret');

        //实例化逻辑层
        $this->logicWechat = new WechatInit();
    }

    public function index() {
        //测试Lemonice
        /*
        $data = array(
            'MsgType'=>'Event',
            'Event'=>'CLICK',
            'EventKey'=>'benefits',
            'FromUserName'=>'oFJj4s0X8rLPta-HwWvSoXWo5H3s',
            'Content'=>'D:17032543400778932555',
        );
        */
        $data = Wechat::postData();
        if (empty($data)) {
            $echoStr = $_GET["echostr"];
            if (\Common\Extend\Wechat::checkSignature()) {
                echo $echoStr;
                exit;
            }
        }
        //设置微信数据
        $this->logicWechat->setDatas($data);
        $this->logicWechat->setText('subscribeQrcode', './public/images/wechat/tel_card.jpg');
        $this->logicWechat->setText('account_id', $this->account_id);

        if (is_array($data) && !empty($data)) {
            //根据不同标识符触发业务处理
            $msgType = strtolower($data['MsgType']);
            if(method_exists($this->logicWechat, $msgType)){
                $this->logicWechat->$msgType();
            }
        }
    }

    /**
     * 物流微信推送
     * @params invoice_no 物流单号
     * @params Content 手机号
     */
    public function sendLogistics() {
        $info['invoice_no'] = I('request.invoice_no', '');
        $info['Content'] = I('request.Content', '');
        $info['FromUserName'] = I('request.FromUserName', '');
        //为了兼容其他公众号，允许可传appid等数据
        $appid = I('request.appid', '');
        $appsecret = I('request.appsecret', '');
        if(!empty($appid) && !empty($appsecret)){
            Wechat::$app_id = $appid;
            Wechat::$app_secret = $appsecret;
        }
        Wechat::$userOpenId = $info['FromUserName'];
        $Logistics = new \Common\Logic\Wechat\LogisticsObj();
        $Logistics->setDatas($info);
        $Logistics->sendWechatLogistics();
    }

    /**
     * 结束某个微信号的客服聊天会话
     */
    public function TSession() {
        header("Content-type: text/html; charset=utf-8");
        $openid = I('request.openid', '', 'trim');
        if (empty($openid)) {
            echo 'openid不能为空！';
            exit;
        }
        $ret = \Common\Extend\wCurl::post('https://api.weixin.qq.com/customservice/kfsession/getsession?access_token=' . \Common\Extend\Wechat::getAccessToken('serviceText') . '&openid=' . $openid);
        $return = json_decode($ret, true);
        if (is_array($return) && !empty($return) && $return['createtime'] > 0) {
            $data = array(
                'kf_account' => $return['kf_account'],
                'openid' => $openid,
            );
            $ret = \Common\Extend\wCurl::post('https://api.weixin.qq.com/customservice/kfsession/close?access_token=' . \Common\Extend\Wechat::getAccessToken('serviceText'), json_encode($data));
            $return = json_decode($ret, true);
            if (isset($return['errcode']) && $return['errcode'] == 0) {
                echo '已T除!';
                exit;
            } else {
                echo 'T除失败';
                exit;
            }
        } else {
            echo '未被接入客服';
            exit;
        }
    }
}
