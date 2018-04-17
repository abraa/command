<?php
//========================================================
// 获取客服 QQ，微信等接口
//========================================================
// Author：9006758
// Date: 2016-12-07
// File: KefuController.class.php
//========================================================
namespace Home\Controller;
use Common\Controller\InitController;
use Common\Extend\RedisQueue;

class KefuController extends InitController
{

    protected $kefu;
    // 获取客服 qq 、微信号等信息接口
    public function index(){
        $group_id = I('request.group', 0, 'intval'); //分组ID
        $channel = I('request.channel', '', 'trim'); //渠道,可为空
        $type_val = I('request.type', 0, 'intval');
        $callback = I('request.callback');
        $this->kefu = is_null($this->kefu) ? I('request.kefu', '', 'trim'): $this->kefu;
        $type = $this->switchType($type_val); //qq：私人QQ，qidian_qq：企点QQ，business_qq：营销QQ，wechat：微信号
		$channel = !in_array($type_val, array(1,2,3)) ? false : $channel;
        $data = $this->getData($type, $group_id, $channel);
        $return = array(
            'error' => isset($data['error']) ? $data['error'] : 0,
            'message' => isset($data['message']) ? $data['message'] : '',
            'data' => isset($data['data']) ? $data['data'] : $data,
        );

        if(!empty($callback)){
			echo $callback.'('.json_encode($return).')';
        }else{
            $this->ajaxReturn($return);
        }
        exit;
	}

    /**
     * 返回客服QQ 类型
     * @param $type_val
     * @return string
     */
    private function switchType($type_val){
        switch($type_val){
            case 1: //企点QQ
                $type = 'qidian_qq';
                break;
            case 2: //营销QQ
                $type = 'business_qq';
                break;
            case 3: //微信号
                $type = 'wechat';
                break;
            default: //私人QQ
                $type = 'qq';
        }
        return $type;
    }

	//获取数据
    private function getData($type = 'qq', $group_id = 0, $channel = ''){
        $group = $this->getRedisGroup('start_'.$type.'_group');
		if($group === false){
			$data['error'] = 1;
            $data['message'] = '服务器错误';
            $data['data'] = array();
            return $data;
		}
        if(!isset($group[$group_id])){
            $data['error'] = 1;
            $data['message'] = '此分组未有缓存数据';
            $data['data'] = array();
            return $data;
        }
        $start_time = $this->getRedisGroupStartTime($group[$group_id]);
        if($start_time <= 0){
            $data['error'] = 1;
            $data['message'] = '此分组未有时间生效';
            $data['data'] = array();
            return $data;
        }

        //检查渠道
        if($channel !== false){
            $channel = $this->checkChannel($channel, $group[$group_id]['channel'][$start_time]);
        }

        $data = isset($group[$group_id]['tagDetail']) ? $group[$group_id]['tagDetail'] : array();
        $result = $this->getRedisQueue('start_'.$type.'_'.$group_id.'_'.$start_time.($channel===false ? '' : '_'.$channel));  //获取队列数据
        $data = !empty($result) ? array_merge($data, $result) : array();

        return $data;
    }

	//检查渠道
    private function checkChannel($channel = '', $channel_array = array()){
        $channel = empty($channel) ? 'default' : $channel;
        if(!in_array($channel, $channel_array)){
            $channel = 'default';
        }
        return $channel;
    }

    //获取队列信息
    private function getRedisQueue($name = ''){
        $queue = RedisQueue::getInstance($name);
        //有传QQ或微信号,检测是否存在于队列中，如果不存在，则从队列中取出一个
        if($this->kefu) {
            $all = $queue->getQueue();
            $exist = array();
            if (!empty($all)) {
                foreach ($all as $val) {
                    $val = substr($val, 0, 6) == 'Array_' ? json_decode(substr($val, 6), true) : array();
                    if (array_search($this->kefu, $val) !== false) {
                        $exist = $val;
                        break;
                    }
                }
            }
        }

        if(!$exist){
            $exist = $queue->pop();
        }
        return $exist;
    }

    //获取分组当前生效的时间
    private function getRedisGroupStartTime($group = array()){
        $start_time = isset($group['start_time']) ? $group['start_time'] : array();
        if(empty($start_time)){
            return 0;
        }
        foreach($start_time as $time){
            if($time <= time()){
                return $time;
            }
        }
        return 0;
    }

    //获取分组信息
    private function getRedisGroup($name = ''){
        $redis = RedisQueue::getInstance($name);
        return $redis->get($name);
    }

}
