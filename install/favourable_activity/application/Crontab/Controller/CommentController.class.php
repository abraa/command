<?php
/**
 * ====================================
 * 订单自动评价
 * ====================================
 * Author: 9004396
 * Date: 2017-04-18 15:54
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: CommentController.class.php
 * ====================================
 */

namespace Crontab\Controller;

use Common\Controller\CrontabController;
use Common\Extend\User;

class CommentController extends CrontabController
{
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        if (is_null($this->orderModel)) {
            $this->orderModel = D('Home/OrderInfoCenter');
        }
    }


    public function auto3GComment()
    {
        $orderInfo = $this->getOrderGoods(87);
        $this->autoComment($orderInfo);
    }

    public function autoQComment()
    {
        $orderInfo = $this->getOrderGoods(14);
        $this->autoComment($orderInfo);
    }


    private function autoComment($orderInfo)
    {
        $logId = $this->insertLog(__CLASS__ . '-' . __FUNCTION__, '开始执行');
        if (empty($orderInfo)) {
            $this->updateLog($logId, '处理0条记录');
        }
        $i = 0;
        foreach ($orderInfo as $order) {

            //兼容PC会员中心订单 已评价的;订单不处理
            $isComment = D('Home/UserCommentCenter')->where(array('rid'=>$order['order_id'],'from_site'=>$order['site_id']))->count();
            if($isComment > 0){
                continue;
            }

            $orderGoods = D('Home/OrderGoodsCenter')
                ->where(array('order_id' => $order['order_id'], 'is_gift' => 0, 'parent_id = 0'))
                ->field('goods_id,extension_code,parent_id')
                ->select();
            if (empty($orderGoods)) {  //商品不存在
                continue;
            }

            $user_id = 0;
            $mobile = !empty($order['mobile']) ? $order['mobile'] : (!empty($order['tel']) ? $order['tel'] : '');
            if (!empty($mobile)) {
                $user = new User();
                $user_id = $user->getUserIdByMobile($mobile);
            }

            $comment = array();
            foreach ($orderGoods as $goods) {
                $goods_id = $goods['goods_id'];
                if (empty($goods_id)) {
                    continue;
                }

                if ($goods['extension_code'] == 'package_buy') {  //套装商品
                    $goodsActivityModel = D('Home/GoodsActivity');
                    $goods_id = $goodsActivityModel->where(array('act_id' => $goods_id))->getField('goods_id');
                    if (empty($goods_id)) {
                        continue;
                    }
                }

                $where = array(
                    'is_on_sale' => 1,
                    'is_promotion' => 0,
                    'is_delete' => 0,
                    'goods_id' => $goods_id
                );
                $isGoods = D('Home/Goods')->where($where)->count();
                if ($isGoods == 0) {
                    continue;
                }

                $commentData = array(
                    'id_value' => $goods_id,
                    'user_name' => '匿名',
                    'user_id' => $user_id,
                    'order_sn' => $order['order_sn'],
                    'time' => time(),
                    'content' => '此用户未填写评价，系统默认好评！',
                    'show_time' => time(),
                    'show_status' => 1,
                    'level' => 5,
                    'is_client' => 0,
                    'like_num' => 0,
                );

                $comment[] = $commentData;
            }
            if (!empty($comment)) {
                $ret = D('Home/Comments')->addAll($comment);
                if($ret){
                    $i++;
                }
            }
            $this->orderModel->where(array('id' => $order['id']))->setField('is_comment',1);
        }
        $this->updateLog($logId, '需处理数据'.count($orderInfo).',处理'.$i.'条记录');
    }


    /**
     * 获取订单商品
     * @param $site_id
     * @return mixed
     */
    public function getOrderGoods($site_id, $limit = 200)
    {
        $this->autoDb($site_id);
        $where = array(
            'shipping_status' => SS_RECEIVED,
            '_string' => 'DATE_SUB(CURDATE(), INTERVAL 15 DAY) <= receive_time',
            'site_id' => $site_id,
            'is_comment' => 0
        );

        $order = $this->orderModel
            ->where($where)
            ->field('order_sn,order_id,mobile,consignee,tel,id,site_id')
            ->limit($limit)
            ->select();
        return $order;
    }

    /**
     * 跟进站点ID切换数据库配置
     * @param $site_id
     */
    private function autoDb($site_id)
    {
        $dbConfig = C('DB_CONFIG');
        foreach ($dbConfig as $config) {
            if ($config['SITE_ID'] == $site_id) {
                C($config['CONFIG']);
            }
        }
    }


}