<?php
/**
 * ====================================
 * 订单评论 控制器
 * ====================================
 * Author: 9006765
 * Date: 2017-02-20
 * ====================================
 * File: OrderCommentController.class.php
 * ====================================
 */

namespace Home\Controller;

use Common\Controller\InitController;
use Common\Extend\Curl;
use Common\Extend\Integral;


class OrderCommentController extends InitController
{

    protected $user_info;
    private $translate_msg = array(
        'evalTarget' => '评价目标',
        'evalDime' => '评价维度',
        'name' => '评价对象名称',
        'channel' => '评价渠道',
        'evalType' => '评价方式',
        'content' => '评价内容',
        'scope' => '评分',
        'evalPerson' => '评价人',
        'evalDate' => '评价时间',
        'msgContent' => '客户留言',
        'code' => '评价对象编码',
        'company' => '公司',
        'dept' => '部门',
        'rid' => '来源订单号',
        'from_site' => '来源站',
    );
    //评价必传参数
    private $param_need = array(
        'target' => 'evalTarget', //评价目标
        'name' => 'name',        //  '评价对象名称'
        'scope' => 'scope',       // 星级
        'order_sn' => 'rid',         //来源订号
    );
    //评价选传参数
    /*private $param_unneed = array(
        'code'=>'code',
        'company'=>'company',
        'dept' =>'dept',
        'mcontent'=>'msgContent',
        'from_site'=>'from_site',
    );*/


    public function __construct()
    {
        parent::__construct();
        $this->user_id = $this->checkLogin();
        $this->user_info = D('users')->getUserInfo($this->user_id);
    }

    /**
     * 废弃，原名 comment
     *
     */
    public function comment_bak()
    {
        if (!isset($_POST['comment']) || empty($_POST['comment'])) {
            $this->error('参数缺失');
        }

        $f_keys = array(
            'target' => 'evalTarget', //评价目标
            'name' => 'name',     //  '评价对象名称'
            'scope' => 'scope', // 星级
            'order_sn' => 'rid', //来源订号
            // 'dime'=>'evalDime',
            // 'channel'=>'channel',
            // 'type'=>'evalType',
            // 'content'=>'content',
        );
        $keys = array(
            'code' => 'code',
            'company' => 'company',
            'dept' => 'dept',
            'mcontent' => 'msgContent',
            'from_site' => 'from_site',
        );

        $p = array();
        $add_p = array();
        $n = 0;
        $post_data = json_decode(str_replace('\\', '', $_POST['comment']));
        if (isset($post_data[0]->order_sn) && !empty($post_data[0]->order_sn)) {
            $where[] = "site_id = " . C('SITE_ID');
            $where[] = "user_id = " . $this->user_id;
            $where[] = "order_sn = " . $post_data[0]->order_sn;
            $OrderInfoCenter = D('OrderInfoCenter');
            $field = 'consignee';
            $order_info = $OrderInfoCenter->field($field)->where(implode(' and ', $where))->find();
        }


        foreach ($post_data as $kk => $pv) {
            foreach ($f_keys as $k => $v) {
                if (!isset($pv->$k) || empty($pv->$k)) {
                    $key_msg = $this->keyTranslate($v);
                    if ($key_msg == '未能识别的键名') {
                        $this->error($key_msg . $k);

                    } else {
                        if ($pv->$k == 0) {
                            $this->error($key_msg . '不能为0！');
                        } else {
                            $this->error('缺少' . $key_msg . '参数');
                        }
                    }
                } else {
                    $p[$kk][$v] = $pv->$k;
                }
                if ($k == 'target' && $pv->$k == '01') {
                    $p[$kk]['evalDime'] = '0102';
                    $p[$kk]['content'] = '质量';
                } else if ($k == 'target' && $pv->$k == '02') {
                    $p[$kk]['evalDime'] = '0202';
                    $p[$kk]['content'] = '售中服务满意度（购物体验）';
                }
            }
            foreach ($keys as $k => $v) {
                if (isset($pv->$k) && !empty($pv->$k)) {
                    $p[$kk][$v] = $pv->$k;
                }
            }
            if (!isset($p['from_site']) || empty($p['from_site'])) {
                $p[$kk]['from_site'] = C('SITE_ID');
            }
            $p[$kk]['channel'] = '01';
            $p[$kk]['evalType'] = '01';
            $p[$kk]['evalPerson'] = empty($order_info['consignee']) ? '匿名' : $order_info['consignee'];
            if ($p[$kk]['evalTarget'] == '01') {
                $add_p[$kk] = $p[$kk];
                $add_p[$kk]['evalDime'] = '0103';
                $add_p[$kk]['content'] = '价格';
            }
        }
        if (!empty($add_p)) {
            foreach ($add_p as $k => $v) {
                if (!empty($v)) {
                    $kk++;
                    $p[$kk] = $v;
                }
            }
        }
        $is_comment = Curl::getApiResponse('http://api.chinaskin.cn/Comment/getComment', array('order_sn' => $p[$kk]['rid'], 'from_site' => $p[$kk]['from_site']));
        if ($is_comment['error'] == 'A0000') {
            $this->error('您已评论');
        }
        $post_data['comment'] = $p;
        $r = Curl::getApiResponse('http://api.chinaskin.cn/Comment/pageComment', $post_data);//var_dump($r);

        //TODO:评价加积分操作 (#7478 会员订单评价)
        if ($r['error'] == 'A00000') {
            $remark = '商品评价获得积分';
            $extra['order_sn'] = $p[$kk]['rid'];
            $extra['user_id'] = $this->user_id;
            $extra['type'] = 2;
            $extra['extend_type'] = 3;
            $extendIntegral = new Integral();
            $extendIntegral->newVariety(C('SITE_ID'), 0, $remark, 0, false, $extra);
        }
        $this->ajaxReturn($r);
    }

    /**
     * 商品评价提交
     * Author: 9006758
     * Date: 2017-04-19
     */
    public function comment()
    {
        $comment = I('request.comment');
        if (empty($comment)) {
            $this->error('参数丢失');
        }
        $order_sn = $comment[0]['order_sn'];

        //获取订单的收货人
        $orderInfoModel = D('OrderInfoCenter');
        $where['site_id'] = C('SITE_ID');
        $where['user_id'] = $this->user_id;
        $where['order_sn'] = $order_sn;
        $order_info = $orderInfoModel->where($where)->field('consignee,is_comment')->find();

        //TODO:查看订单是否已经评价
        if ($order_info['is_comment'] == 1) {
            $this->error('您已评论');
        } else {
            $exist = D('UserCommentCenter')->where(array('rid' => $order_sn, 'from_site' => C('SITE_ID')))->count();
            if ($exist) {
                $this->error('您已评论');
            }
        }

        //评价人-商品满意度的
        $evalPerson = empty($order_info['consignee']) ? '匿名' : $order_info['consignee'];
        //商品评价的用户名，会员昵称-》收货人-》匿名
        $user_id = $this->getUserId();
        $user_name = D('Users')->where(array('user_id' => $user_id))->getField('user_name');
        if (!$user_name) {
            $user_name = $evalPerson;
        }

        $sub_site_comment = array();//分站点商品评论集合
        $msg_content = array();//商品评论集合
        $price_comment_arr = array();//价格满意度集合
        $comment_gallery = array();//评论相册集合
        $data_arr = array();//商品满意度调查集合
        foreach ($comment as $val) {
            //订单满意度数据
            $data['evalTarget'] = $val['target'];//评价目标:01产品,02服务
            $data['code'] = !empty($val['code']) ? $val['code'] : '';//评价对象编码（可以为客服编码或者产品编码等）
            $data['name'] = $val['name'];//评价对象名称（可以为客服名称或者产品名称、公司等）
            $data['company'] = '';//所属公司
            $data['dept'] = '';//所属部门
            $data['channel'] = '01';//评价渠道:01定单评价,0201呼叫中心呼出,0202呼叫中心呼入,0301微信,0302个人QQ,0303企点QQ,0304营销QQ,0305公众号
            $data['evalType'] = '01';//评价方式：01定单评价，02电话打分,03客服推评价链接04客服投诉处理
            $data['scope'] = $val['scope'];//评分:1,2,3,4,5五个等级
            $data['evalPerson'] = $val['user_show'] == 1 ? '匿名' : $evalPerson;//评价人
            $data['msgContent'] = $val['mcontent'];//客户留言
            $data['rid'] = $val['order_sn'];//订单号
            $data['remarks'] = $val['order_sn']; //备注订单号
            $data['from_site'] = C('SITE_ID');//所属订单

            if ($data['evalTarget'] == '01') {
                $data['evalDime'] = '0102';
                $data['content'] = '质量';

                //TODO:每条商品评论生成对应价格满意度存入集合
                $price_comment = $data;
                $price_comment['evalDime'] = '0103';
                $price_comment['content'] = '价格';
                $price_comment_arr[] = $price_comment;

                if (!empty($data['msgContent'])) {
                    //TODO:商品的评价内容-生成集合
                    $msg_content[] = $data['msgContent'];

					// 分站点商品评论集合数据收集，只收集前台订单并且，对应的商品应该存在
					if($val['order_sn'][6] == 1){ // 后台订单为 2，前台订单为 1
						$good_exist = D('Goods')->where(array('goods_id' => $val['goods_id']))->count();
						if($good_exist){
							$sub_site_comment[] = array(
								'id_value' => $val['goods_id'],//商品id
								'user_name' => $user_name,//用户名
								'user_id' => $user_id,//用户id
								'time' => time(),//评论时间
								'show_time' => 0,//展示的时间
								'content' => $data['msgContent'],//评论的内容
								'status' => 0,//0表示显示，1表示垃圾桶
								'show_status' => 0,//0表示不显示，1表示显示
								'level' => $data['scope'],//级别，在这里用评分填充
								'is_client' => 1,//评论类型：0：系统 1：客户
								'like_num' => mt_rand(800, 12000),//喜欢数，认为有用数
								'order_sn' => $val['order_sn'],//订单号
							);
							
							//评论相册
							if (!empty($val['gallery'])) {
								$comment_gallery[$val['goods_id']] = $val['gallery'];
							}
						}else{
							unset($val['goods_id']);
						}
					} else {
						unset($val['goods_id']);
					}
                }

            } else if ($data['evalTarget'] == '02') {
                $data['evalDime'] = '0202';
                $data['content'] = '售中服务满意度（购物体验）';
                $data['msgContent'] = '';//置空，以便后面随机给评价内容
            }

            //TODO:判断是否存在必传的数据
            foreach ($this->param_need as $param) {
                if (!isset($data[$param])) {
                    $this->error('参数' . $param . '丢失');
                } else if (empty($data[$param])) {
                    if (is_numeric($data[$param]) && $data[$param] <= 0) {
                        $this->error($this->translate_msg[$param] . '不能为0');
                    }
                    $this->error($this->translate_msg[$param] . '值丢失');
                }
            }

            $data_arr[] = $data;
        }

        //TODO:商品的评价内容-将多个奖品的评论内容随机分配给 购物体验、商品包装、物流速度 作为评价内容
        if (!empty($msg_content)) {
            foreach ($data_arr as &$value) {
                if ($value['evalTarget'] == '02') {
                    $value['msgContent'] = $msg_content[array_rand($msg_content)];
                }
            }
        }

        //TODO:添加评论
        $post_data['comment'] = array_merge($data_arr, $price_comment_arr);
        $return = Curl::getApiResponse('http://api.chinaskin.cn/Comment/pageComment', $post_data);

        if ($return['error'] == 'A00000') {
            //TODO:评价加积分操作 (#7478 会员订单评价)
            $this->integralAdd($order_sn, $this->user_id);

            /* TODO:商品评论数据存入分站点 */
            if (!empty($sub_site_comment)) {
                //TODO:生成评论图片及缩略图
                $gallery = $this->makeCommentGallery($comment_gallery);
                //TODO:评论入库，以及相册入库
                $this->commentAdd($sub_site_comment, $gallery);
            }
            //TODO:更新订单表中评论状态
            $orderInfoModel->where($where)->setField('is_comment', 1);
            $this->success();
        } else {
            $this->error($return['message']);
        }
    }

    /**
     * 订单列表
     * @author 9004396
     * @date  2015-4-22 
     */
    public function getOrderGoodsComment()
    {
        $id = I('param.id', 0, 'intval');
        $comment = array();
        if (empty($id)) {
            $this->error('订单不存在');
        }
        $orderInfo = D('OrderInfoCenter')->field('order_sn,site_id')->find($id);
        if (empty($orderInfo)) {
            $this->error('订单不存在');
        }
        $order_sn = $orderInfo['order_sn'];
        $goodsData = D('OrderGoodsCenter')->where(array('order_sn' => $order_sn, 'site_id' => $orderInfo['site_id'], 'parent_id' => 0))->select();
        $goodsIds = array();
        $packageIds = array();
        if (!empty($goodsData)) {
            foreach ($goodsData as $goods) {
                if ($goods['extension_code'] == 'package_buy') {
                    $packageIds[] = $goods['goods_id'];
                } else {
                    $goodsIds[] = $goods['goods_id'];
                }
            }

            //判断是否存在套装
            if (!empty($packageIds)) {
                $package = D('GoodsActivity')
                    ->where(array('act_id' => array('IN', $packageIds)))
                    ->field('goods_id')
                    ->select();
                if (!empty($package)) {
                    foreach ($package as $pack) {
                        if (!empty($pack['goods_id'])) {
                            $goodsIds[] = $pack['goods_id'];
                        }
                    }
                }
            }

            $comment = D('Comments')->alias('c')
                ->field('FROM_UNIXTIME(c.time,"%Y-%m-%d") AS time,c.content as m_content,c.id,g.goods_thumb,g.original_img,g.goods_img')
                ->join('__GOODS__ AS g ON g.goods_id=c.id_value', 'LEFT')
                ->where(array('c.id_value' => array('IN', $goodsIds), 'c.order_sn' => $order_sn, 'c.z_date' => array('eq', ''), 'c.user_id' => $this->user_id))
                ->select();
            foreach ($comment as &$item) {
                $item['goods_thumb'] = C('domain_source.img_domain') . $item['goods_thumb'];
                $item['goods_img'] = C('domain_source.img_domain') . $item['goods_img'];
                $item['original_img'] = C('domain_source.img_domain') . $item['original_img'];
            }
        }
        $this->success($comment);
    }

    /**
     * 追加评价
     * @author 9004396
     * @date  2015-4-19
     */
    public function pursue()
    {
        $reply = I('post.reply');
        if (empty($reply) || !is_array($reply)) {
            $this->error('参数异常');
        }

        $commentId = array();
        foreach ($reply as $item) {
            if (mb_strlen($item['content'], 'UTF8') < 6) {
                $this->error('投诉信息最少6个字');
            }
            $commentId[] = $item['commentId'];
        }
        //过滤已评论信息
        $commentModel = D('Comments');
        $comment = $commentModel
            ->where(array('id' => array('IN', $commentId), 'z_date' => array('eq', ''), 'user_id' => $this->user_id))
            ->field('id')
            ->select();
        $commentId = array();
        foreach ($comment as $v) {
            $commentId[] = $v['id'];
        }
        if (empty($commentId)) {
            $this->error('评论异常，请联系客服');
        }

        $result = false;
        foreach ($reply as $item) {
            if (!in_array($item['commentId'], $commentId)) {
                continue;
            }
            $galleryImg = array();
            if (!empty($item['gallery'])) {  //处理图片
                $galleryImg = $this->makeCommentGallery(array($item['gallery']));
                $galleryImg = current($galleryImg);
            }

            $commentData = array(
                'z_content' => $item['content'],
                'z_date' => time(),
            );

            $res = $commentModel->where(array('id' => $item['commentId']))->save($commentData);

            if ($res) {
                if (!empty($galleryImg)) {
                    $gImg = array();
                    foreach ($galleryImg AS $gallery) {
                        $gImg[] = array(
                            'comment_id' => $item['commentId'],
                            'img_url' => $gallery['img_url'],
                            'type' => 1
                        );
                    }
                    D('CommentsGallery')->addAll($gImg);
                }
                $result = true;
            } else {
                if (!empty($galleryImg)) {
                    $site_dir = (C('site_id') == '87') ? '3g' : 'q';
                    $dir = APP_ROOT . 'res/' . $site_dir . '/';
                    foreach ($galleryImg as $img) {
                        @unlink($dir . $img);
                    }
                }
            }
        }

        if ($result) {
            $this->success();
        } else {
            $this->error('评价失败');
        }
    }


    /**
     * 获取评论信息，用于判断是否评论
     *
     */
    public function getComment()
    {
        $post_data['order_sn'] = I('post.order_sn', 0);
        if (empty($post_data['order_sn'])) {
            $r = array('error' => 'A00001', 'message' => '订单号不能为空', 'data' => '');
            $this->ajaxReturn($r);
        }
        $post_data['from_site'] = C('SITE_ID');
        $r = Curl::getApiResponse('http://api.chinaskin.cn/Comment/getComment', $post_data);
        $this->ajaxReturn($r);
    }


    /**
     * 字段名称对照
     * @param $k
     * @return string
     */
    private function keyTranslate($k)
    {

        $data = array(
            'evalTarget' => '评价目标',
            'evalDime' => '评价维度',
            'name' => '评价对象名称',
            'channel' => '评价渠道',
            'evalType' => '评价方式',
            'content' => '评价内容',
            'scope' => '评分',
            'evalPerson' => '评价人',
            'evalDate' => '评价时间',
            'msgContent' => '客户留言',
            'code' => '评价对象编码',
            'company' => '公司',
            'dept' => '部门',
            'rid' => '来源订单号',
            'from_site' => '来源站',
        );
        if (isset($data[$k])) {
            return $data[$k];
        } else {
            return '未能识别的键名';
        }
    }


    /*
    *	检查当前是否登录
    *	@return int [user_id]
    */
    private function checkLogin()
    {
        $user_id = $this->getUserId();  //用户ID
        if ($user_id <= 0) {
            $this->error($this->not_login_msg);  //没登录
        }
        return $user_id;
    }

    /*
    * 获取当前登录用户ID
    * @return int [user_id]
    */
    private function getUserId()
    {
        $user_id = D('users')->getUser('user_id');  //用户ID
        $user_id = $user_id ? $user_id : 0;
        return $user_id;
    }

    /**
     * 评价加积分操作
     * @param $order_sn    订单编号
     * @param int $user_id 会员user_id
     */
    private function integralAdd($order_sn, $user_id = 0)
    {
        $remark = '商品评价获得积分';
        $extra['order_sn'] = $order_sn;
        $extra['user_id'] = $user_id ? $user_id : $this->getUserId();
        $extra['type'] = 2;
        $extra['extend_type'] = 3;
        $extendIntegral = new Integral();
        $extendIntegral->newVariety(C('SITE_ID'), 0, $remark, 0, false, $extra);
    }

    /**
     * 评论相册存放以及生成缩略图
     * @param array $gallery 评论相册集合
     * @return array
     */
    private function makeCommentGallery($gallery = array())
    {
        $data = array();
        if (!empty($gallery)) {
            $site_dir = (C('site_id') == '87') ? '3g' : 'q';
            $dir = APP_ROOT . 'res/' . $site_dir . '/';
            $img_dir = 'pic/comments/' . date('Ym') . '/';
            $save_path = $dir . $img_dir;
            if (!file_exists($save_path)) {
                makeDir($save_path);
            }

            foreach ($gallery as $key => $val) {
                $source = array();
                foreach ($val as $k => $v) {
                    if (!empty($v)) {
                        $img_name = time() . mt_rand(100000000, 999999999) . '.jpg';//图片名称
                        $img_source = base64_decode(str_replace('data:image/jpeg;base64,/', '/', $v));
                        $save_name = $img_dir . $img_name;
                        $res = file_put_contents($dir . $save_name, $img_source);
                        if ($res !== FALSE) {
                            $source[$k] = array(
                                'img_url' => $save_name
                            );
                        }
                    }
                }
                $data[$key] = $source;
            }
        }
        return $data;
    }

    /**
     * 评论入库 和 评论相册入库
     * @param $comment_data 评论集合
     * @param array $gallery 评论相册集合，已经包含原图跟缩略图的
     */
    private function commentAdd($comment_data, $gallery = array())
    {
        $commentModel = D('Comments');

        foreach ($comment_data as $val) {
            //评论入库
            $ins_id = $commentModel->add($val);

            //相册入库
            if ($ins_id && !empty($gallery)) {
                $this->commentGalleryAdd($gallery, $ins_id, $val['id_value']);
            }
        }
    }

    /**
     * 评论相册入库
     * @param $gallery      评论相册集合
     * @param $comment_id   评论id
     * @param $goods_id     商品id
     */
    private function commentGalleryAdd($gallery, $comment_id, $goods_id, $type = 0)
    {
        $commentsGalleryModel = D('CommentsGallery');
        foreach ($gallery as $val) {
            if (!empty($gallery[$goods_id])) {
                foreach ($gallery[$goods_id] as $val) {
                    $gallery_data[] = array(
                        'comment_id' => $comment_id,
                        'img_url' => $val['img_url'],
                        'type' => $type
                    );
                }
                $commentsGalleryModel->addAll($gallery_data);
            }
        }
    }


}