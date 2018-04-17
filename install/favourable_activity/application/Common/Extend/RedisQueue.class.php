<?php
/**
 * redis队列
 * Author: Lemonice  (9009123)
 * Email: chengciming@126.com
 * Date: 2016-12-07 09:40
 * File: RedisQueue.class.php
 *
 * 基本redis实现的环形消息队列
 * 用法:
 * use Com\RedisQueue;
 * $queue = RedisQueue::getInstance('msg');
 *
 * 加入队列
 * $queue->push('aaaaaa');
 * $queue->push('bbbbb');
 * 读取队列
 * $value = $queue->pop()
 *
 * 删除队列
 * $queue->flushQueue();
 */
namespace Common\Extend;
use Think\Cache\Driver\Redis;

class RedisQueue extends Redis {
    static public $timeout = 1;
    static public $queueName = 'tms_queue';

    static public $_config = array();

    /**
     * 取得缓存类实例
     * @static
     * @access public
     * @return mixed
     */
    public static function getInstance($queueName) {
        //if (C('DATA_CACHE_TYPE') != 'Redis') exit('DATA_CACHE_TYPE DO NOT Support Redis');

        //当前队列名称
        self::$queueName = 'tms_' . $queueName;

        RedisQueue::$_config = C('REDIS');

        static $_instance = array();
        if (!isset($_instance[self::$queueName])) {
            $_instance[self::$queueName] = new RedisQueue(self::$_config);
        }

        return $_instance[self::$queueName];
    }

    //设置队列名称
    public static function setQueueName($name) {
        self::$queueName = 'tms_' . $name;
    }

    /**
     * 添加队列(lpush)
     * @param string $value
     * @return int 队列长度
     */
    public function push($value) {
        return $this->lPush(self::$queueName, (is_array($value) ? 'Array_'.json_encode($value) : $value));
    }

    /**
     * 读取队列,将读取到的值放在队列最左侧
     * @param bool $lPush 是否开启自动添加队列
     * @return string|null
     */
    public function pop($lPush = true) {
        $result = $this->brPop(self::$queueName, self::$timeout);

        if (empty($result)) {
            return $result;
        } else {
            if($lPush){
                //将取出来的值添加到最队列最左侧
                $this->lPush(self::$queueName, $result[1]);
            }
            return substr($result[1],0,6)=='Array_' ? json_decode(substr($result[1],6),true) : $result[1];
        }
    }

    /**
     * 删除一个消息队列
     */
    public function flushQueue() {
        $this->delete(self::$queueName);
    }

    /**
     * 返回队列长茺
     * @return int
     */
    public function len() {
        return $this->LLEN(self::$queueName);
    }

    public function getQueue(){
        return $this->lRange(self::$queueName, '0', '-1');
    }
}

?>