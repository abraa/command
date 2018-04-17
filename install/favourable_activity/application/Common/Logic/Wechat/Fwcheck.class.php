<?php
/**
 * ====================================
 * 微信公众号 - 防伪码
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 15:54
 * ====================================
 * File: Fwcheck.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;
use Common\Extend\Curl;
use Common\Extend\Time;
use Common\Logic\Wechat\WechatData;

class Fwcheck extends WechatData{
    /**
     * 返回防伪码检测结果
     */
    public function check() {
        $info = $this->getDatas();
        if (!class_exists('nusoap_base')) {
            import('Common/Extend/Nusoap');
        }
        $codearr = explode(':', $info['Content']);
        $code = $codearr[1];
        Wechat::$userOpenId = $info['FromUserName'];
        if ($codearr[0] == 'C') {
            $postData = array(
                'fwcode' => $code,
                'referer' => 'http://www.zxfw315.com/Gm/Default.asp?FWcode=' . $code,
                'ip' => get_client_ip()
            );
            $ret = Curl::post('http://www.cn315fw.com/fwapi/query/result', $postData);
            preg_match('/<div id=\"result\" .*?>.*?<\/div>/ism', $ret, $matches);
            $content = trim(strip_tags($matches[0]));
        } else if ($codearr[0] == 'D') {
            $postData['head'] = array('user' => 'csreport', 'pass' => '4d4738784d3277335a6', 'source' => '微信');
            $postData['body'][] = array('msgId' => time(), 'qrcode' => $code, 'mobile' => '');
            $ret = Curl::request('http://afs.cjlady.com/verify', array('msg' => json_encode($postData)));
            if (!empty($ret)) {
                $ret = json_decode($ret);
                if (!isset($ret->result)) {
                    $content = '查询失败，系统开小差了，稍后试试哦。';
                } else if ($ret->result[0]->verifyResult) {
                    $content = '您输入的防伪码：' . $code . "\n" . '查询结果： 为正品,请放心使用。For genuine, please rest assured that the use of.';
                } else {
                    $content = '您输入的防伪码：' . $code . "\n" . '查询结果： 没有这个防伪码，谨防假冒或者重新核对输入。Be cautious if no anti-fake code here, or please check again.';
                }
            } else {
                $content = '网络出问题了，稍后试试哦。';
            }
        } else {
            $client = new \soapclient1('http://wsu.t3315.com/T3315WebSrvSetup/T3315WebSrv.asmx?WSDL', true);
            $err = $client->getError();
            if ($err) {
                $content = '防伪验证查询连接失败，请稍后再试';
            } else {
                $cid = '4366,4367';            //企业编号 必须与入网企业编码一致
                $queryPwd = '0000';        //企业查询密码
                $parm = array('QryChannel' => '10000', 'FwCode' => $code, 'CompanyId' => $cid, 'QueryPwd' => $queryPwd, 'VerifyCode' => '', 'TermIp' => get_client_ip(), 'AddrName' => '');
                $result = $client->call('FW', array('parameters' => $parm));
                $content = '您输入的防伪码：' . $code . "\n";
                if ($client->fault) {
                    $content = $result;
                } else {
                    $err = $client->getError();
                    if ($err) {
                        $content = '防伪验证查询连接失败，请稍后再试';
                    } else {
                        $pos = strpos($result, '|');
                        if ($pos === false) {
                            $content = $result;
                        } else {
                            $returnary = explode('[|]', $result);
                            $content .= '查询结果： ' . str_replace($code, '', $returnary[17]);
                        }
                    }
                }
            }
        }
        $ret = Wechat::serviceText($content);
        $content = isset($err)&&!empty($err) ? $err : $content;
        D('BindUser')->addWxMsg(array(
            'open_id'=>$info['FromUserName'],
            'content'=>$content,
            'type'=>'防伪查询推送',
            'errcode'=>$ret['errcode'],
            'errmsg'=>$ret['errmsg'],
            'addtime'=>Time::gmTime(),
        ));
    }

    /**
     * 提示如何查询防伪码信息
     */
    public function help() {
        $content = '1.回复标签款式+标签码查询真伪（如A:44559898566554）' . "\n";
        $content .= '2.点击<a href=\'http://mp.weixin.qq.com/s/TFFSj--1ChOcqfG-mBqjdA\'>产品使用</a>，查看护肤品使用方法。' . "\n";
        $content .= '3.点击<a href=\'http://mp.weixin.qq.com/s/DRKrXJkzZPqGfkJY7uP_RA\'>产品使用</a>，查看美肤仪器使用方法。';
        Wechat::serviceText($content);
    }

    /**
     * 判断输入的文本内容是否防伪码格式
     * @return bool
     */
    public function isFwcode() {
        $code = trim($this->getData('Content'));
        $type = array('A', 'B', 'C', 'D');
        $arr = explode(':', $code);
        if (in_array($arr[0], $type) && preg_match('/\w/', $arr[1])) {
            return true;
        } else {
            return false;
        }
    }
}