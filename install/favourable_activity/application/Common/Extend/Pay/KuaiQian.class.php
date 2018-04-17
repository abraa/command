<?php
/**
 * ====================================
 * 快钱 API接口 - 第三方平台
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-11 14:00
 * ====================================
 * File: KuaiQian.class.php
 * ====================================
 */
namespace Common\Extend\Pay;

class KuaiQian extends Pay{
    public function __construct(){
        parent::__construct();
        $this->pay = new \Common\Extend\Pay\KuaiQian\KuaiQian();
        $this->pay->setAccount(KUAIQIAN_ACCOUNT);
        $this->pay->setSecret(KUAIQIAN_SECRET);
    }

    /**
     * 生成支付HTML代码
     */
    public function getCode(){
        $params = $this->getDatas();
        $bankId = $params['bank_id'];
        $orderAmount = floatval($params['order_amount']) * 100;    //字符金额 以 分为单位 比如 10 元， 应写成 1000

        //获取支付编码
        $payType = $this->pay->getPayType($bankId);
        if($payType == '00'){
            $bankId = '';
        }

        $orderTime = date('YmdHis');

        $respondUrl = $this->getRespondUrl();
        $notifyUrl = $this->getNotifyUrl();
        $account = $this->pay->getAccount();

        $this->pay->setData('inputCharset', '1');
        $this->pay->setData('pageUrl', $respondUrl);
        $this->pay->setData('bgUrl', $notifyUrl);
        $this->pay->setData('version', 'mobile1.0');
        $this->pay->setData('language', '1');
        $this->pay->setData('signType', '4');
        $this->pay->setData('merchantAcctId', $account);
        $this->pay->setData('payerName', '');
        $this->pay->setData('payerContactType', '');
        $this->pay->setData('payerContact', '');
        $this->pay->setData('orderId', $params['order_sn']);
        $this->pay->setData('orderAmount', $orderAmount);
        $this->pay->setData('orderTime', $orderTime);
        $this->pay->setData('productName', '');
        $this->pay->setData('productNum', '');
        $this->pay->setData('productId', '');
        $this->pay->setData('productDesc', '');
        $this->pay->setData('ext1', '');
        $this->pay->setData('ext2', '');
        $this->pay->setData('productNum', '');
        $this->pay->setData('payType', $payType);
        $this->pay->setData('bankId', $bankId);
        $this->pay->setData('redoFlag', '0');
        $this->pay->setData('pid', '');

        $data = $this->pay->getSignData();
        $sign = $this->pay->makeSign($data);
        $signmsgval = $this->pay->buildString($data);

        $url = $this->pay->getGateWay().'?'.$signmsgval."&signMsg=".$sign;

        $button = '<form method="get" action="'.$url.'">';
        $button .= "<input type='hidden' name='inputCharset' value='1' />";
        $button .= "<input type='hidden' name='pageUrl' value='" . $respondUrl . "' />";
        $button .= "<input type='hidden' name='bgUrl' value='" . $notifyUrl . "' />";
        $button .= "<input type='hidden' name='version' value='mobile1.0' />";
        $button .= "<input type='hidden' name='language' value='1' />";
        $button .= "<input type='hidden' name='signType' value='4' />";
        $button .= "<input type='hidden' name='signMsg' value='" . $sign . "' />";
        $button .= "<input type='hidden' name='merchantAcctId' value='" . $account . "' />";
        $button .= "<input type='hidden' name='payerName' value='' />";
        $button .= "<input type='hidden' name='payerContactType' value='' />";
        $button .= "<input type='hidden' name='payerContact' value='' />";
        $button .= "<input type='hidden' name='orderId' value='" . $params['order_sn'] . "' />";
        $button .= "<input type='hidden' name='orderAmount' value='" . $orderAmount . "' />";
        $button .= "<input type='hidden' name='orderTime' value='" . $orderTime . "' />";
        $button .= "<input type='hidden' name='productName' value='' />";
        $button .= "<input type='hidden' name='productId' value='' />";
        $button .= "<input type='hidden' name='productDesc' value='' />";
        $button .= "<input type='hidden' name='ext1' value='' />";
        $button .= "<input type='hidden' name='ext2' value='' />";
        $button .= "<input type='hidden' name='productNum' value='' />";
        $button .= "<input type='hidden' name='payType' value='" . $payType . "' />";
        $button .= "<input type='hidden' name='bankId' value='" . $bankId . "' />";
        $button .= "<input type='hidden' name='redoFlag' value='0' />";
        $button .= "<input type='hidden' name='pid' value='' />";
        $button .= "<input id='kuaiqian_submit' type='submit' value='快钱支付' />";
        $button .= "</form>";

        return $button;
    }

    /**
     * 异步回调
     * @return bool
     */
    public function notify(){
        $respondUrl = $this->getRespondUrl();  //需要返回同步地址给快钱
        $result = $this->pay->notify($respondUrl);
        if($result === false){  //校验失败，数据有问题
            $error = $this->pay->getError();
            if(!empty($error)){
                $this->setError($error);
            }
            return false;
        }else{  //校验成功，有数据
            return $result;
        }
    }
}