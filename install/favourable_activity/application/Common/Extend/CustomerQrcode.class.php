<?php
/**
 * 客服二维码 相关扩展类
 * Author: 9006758
 * Date: 2017-05-18
 * File: CustomerQrcode.class.php
 */
namespace Common\Extend;
use Common\Extend\RedisQueue;

class CustomerQrcode{

	//客服等级
    private $_level = array(
        'A' => 1,
        'B' => 2,
        'C' => 3,
        'D' => 4,
    );
    //二维码轮询规则
    private $_slideLevel = 'ABCABA';
    private $_queueKey = 'kefu_second_line_qrcode';
    private $dbModel;
    private $_redis;

    public function __construct(){
        $this->dbModel = new \Home\Model\QrcodeModel();
        $this->_redis = new RedisQueue();
        RedisQueue::setQueueName($this->_queueKey);
    }

	//获取二维码
	public function getQrcode($type=0){
        $qrcode_id = $this->_redis->pop(false);
        if(!empty($qrcode_id)){
            $res = $this->dbModel->getQrcodeOne($qrcode_id);
            if(!empty($res)){
                $this->_redis->push($res['id']); //重回队列
                return $res;
            }else{
                return $this->getQrcode($type);
            }
        }else{
            //重置队列
            if(!$this->flushQueue($type)){
                return array();
            }
            return $this->getQrcode($type);
        }
	}

    /**
     * 刷新队列
     * @param int $qrcode_type 二维码类型
     * @return bool
     */
    public function flushQueue($qrcode_type=0){
        $this->_redis->flushQueue();    //清空队列
        $qrcodes = $this->dbModel->getQrcodeByLevel($qrcode_type, 'id,kefu_level');
        if(!empty($qrcodes)){
            for($i=0;$i<strlen($this->_slideLevel);$i++){
                $level = $this->_level[$this->_slideLevel[$i]];
                foreach($qrcodes as $key=>$val){
                    if($val['kefu_level'] == $level){
                        $this->_redis->push($val['id']);
                    }
                }
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * 查看队列数据
     */
    public function queueShow(){
        return $this->_redis->getQueue();
    }

}
?>