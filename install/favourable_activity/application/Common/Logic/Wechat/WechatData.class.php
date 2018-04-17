<?php
/**
 * ====================================
 * 微信公众号 - 微信数据相关的处理
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 14:44
 * ====================================
 * File: WechatData.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;

class WechatData {
    /**
     * 微信数据
     * @var array
     */
    protected $data = array();
    /**
     * 文字定义，用于区分不同公众号
     * @var array
     */
    protected $text = array();
    public function __construct() {

    }

    /**
     * 不返回任何数据给微信
     */
    protected function sayNothing(){
        echo '';
        exit;
    }

    /**
     * 记录会员在公众号的最后活动时间
     * @param $activity_type
     * @return bool|mixed
     */
    protected function user_activity_log($activity_type){
        $data['last_activity_time'] = time();
        $data['activity_type'] = $activity_type;

        $openid = $this->getData('openid');

        $user_activity = new \Cpanel\Model\UserActivityModel();
        $row = $user_activity->where("openid = '$openid'")->find();
        if($row){
            $ret = $user_activity->where("openid = '$openid'")->save($data);
        }else{
            $data['openid'] = $openid;
            $ret = $user_activity->add($data);
        }
        return $ret;
    }

    /**
     * 初始化类相关的数据
     * @param int $activity_type
     */
    protected function init($activity_type = NULL){
        if(empty(Wechat::$app_id)){
            Wechat::$app_id = C('appid');
            Wechat::$app_secret = C('appsecret');
        }
        Wechat::$userOpenId = $this->getData('FromUserName');    //发送方账号openid
        if(!is_null($activity_type)){
            $this->user_activity_log($activity_type);        //记录最后活动时间
        }
    }

    /**
     * 设置微信数据 - 单个字段
     * @param string $field
     * @param $value
     */
    public function setData($field = '', $value){
        $this->data[$field] = $value;
    }

    /**
     * 获取微信数据 - 单个字段
     * @param string $field
     * @return null
     */
    public function getData($field = ''){
        return isset($this->data[$field]) ? $this->data[$field] : NULL;
    }

    /**
     * 设置微信数据
     * @param array $data
     */
    public function setDatas($data = array()){
        $this->data = $data;
    }

    /**
     * 获取微信数据
     * @return array
     */
    public function getDatas(){
        return $this->data;
    }

    /**
     * 获取不同公众号文字
     * @return array
     */
    public function getText($field = ''){
        return isset($this->text[$field]) ? $this->text[$field] : NULL;
    }

    /**
     * 设置不同公众号文字
     */
    public function setText($field = '', $value = ''){
        $this->text[$field] = $value;
    }

    /**
     * 获取不同公众号文字 - 获取所有文字
     * @return array
     */
    public function getTexts(){
        return $this->text;
    }

    /**
     * 设置不同公众号文字 - 设置所有文字
     */
    public function setTexts($text = array()){
        $this->text = $text;
    }
}