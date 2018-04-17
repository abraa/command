<?php
/**
 * ====================================
 * 订单 控制器
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2016-06-29 15:31
 * ====================================
 * File: OrderController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;


class OrderController extends InitController{
	private $not_login_msg = '您还未登录，请先登录';  //当前没登录的提示信息
	
	private $dbModel = NULL;  //储存地址数据表对象
	
	//private $user_id = 0;  //当前登录的ID
	
	private $not_login_action = array();  //不需要登录的方法
    //订单状态
    private $order_status_arr = array(
        OS_UNCONFIRMED,     // 未确认
        OS_CONFIRMED,       // 已确认
        OS_CANCELED,        // 已取消
        OS_INVALID,         // 无效
        OS_RETURNED,        // 退货
        OS_ABNORMAL,        // 异常
        OS_LOST,            // 丢失
//        OS_ISDELETED      //假删除标记
    );
	
	public function __construct(){
		parent::__construct();
		$this->dbModel = D('OrderInfo');
//		session('user_id', 6540);  //测试的
//		C('SITE_ID',3);  //测试的
		if(isset($this->not_login_action) && !in_array(ACTION_NAME, $this->not_login_action)){
			$this->user_id = $this->checkLogin();  //检查登录，获取用户ID
		}
	}
	
	/*
	*	订单列表 - 自己的订单列表 - 会员中心
	*	@Author 9009123 (Lemonice)
	*	@return exit & Json
	*/
	public function lists(){
        $user_id = $this->getUserId();
        $status = I('request.status',0,'intval');  //0=全部，1=未支付，2=货到付款，3=待发货,4=已发货,5=已完成,6=在线支付
		$page = I('request.page',1,'intval');
		$pageSize = I('request.pageSize',0,'intval');
		$site_id = C('SITE_ID');  //当前站点ID
		
		$where = array();
		if($status > 0){  //不是获取全部
			switch($status){
				case 1:  //未支付
					$where[] = "pay_id > 1 and (pay_status = " . PS_UNPAYED." OR pay_status = ".PS_PAYING.")";
				break;
				case 2:  //货到付款
					$where[] = "pay_id = 1";
				break;
                case 3: //待发货
                    $where[] = 'shipping_status = '.SS_PREPARING.' and order_status = '.OS_CONFIRMED;
                break;
                case 4: //已发货
                    $where[] = 'shipping_status = '.SS_SHIPPED.' and order_status = '.OS_CONFIRMED;
                break;
                case 5: //已完成
                    $where[] = 'shipping_status = '.SS_RECEIVED . ' and order_status = '.OS_CONFIRMED;
                break;
                case 7: //待评价订单
                    $table_name = D('OrderInfoCenter')->getTableName();
                    $user_comment_table = D('UserCommentCenter')->getTableName();
                    $where['_string'] = "NOT EXISTS(select id from $user_comment_table as uc WHERE uc.rid=$table_name.order_sn) and $table_name.is_comment=0";
					$where[] = "shipping_status = ".SS_RECEIVED . " and order_status = ".OS_CONFIRMED." AND  MONTH(FROM_UNIXTIME(add_time,'%Y-%m-%d')) > MONTH(now())-2";
                    break;
				case 6:  //在线支付
					$where[] = "pay_id > 1 and pay_status = " . PS_PAYED;
				default:  //全部
					
				break;
			}
			
		}
		$where[] = "site_id = '$site_id'";
		$where[] = "user_id = '$user_id'";

        //#7588 屏蔽删除的订单
        $where[] = 'order_status in('.implode(',', $this->order_status_arr).')';

		//$where[] = "(pay_status != '".PS_UNPAYED."' OR pay_id = 1)";  //不显示未付款
        $OrderInfoCenter = D('OrderInfoCenter');  //会员中心的订单表
		$field = 'id,order_id,order_sn,order_status,shipping_status,postscript,integral_money as integral,pay_status,pay_id,pay_name,money_paid,order_amount,add_time';
		$order = 'add_time desc';
        $data = $OrderInfoCenter->getPage($field,(!empty($where)?implode(' and ',$where):''), $order, $page, $pageSize, true);
        
		$this->success($data);
	}
	
	/*
	*	订单列表 - 自己的订单列表 - 会员中心
	*	@Author 9009123 (Lemonice)
	*	@return exit & Json
	*/
	public function info(){
        $user_id = $this->getUserId();
        $order_id = I('request.order_id',0,'intval');
		$order_sn = I('request.order_sn','','trim');
		$site_id = C('SITE_ID');  //当前站点ID
		
		$where = array();
		if($order_id > 0){
			$where[] = "order_id = " . $order_id;
		}
		if($order_sn > 0){
			$where[] = "order_sn = " . $order_sn;
		}
		if(empty($where)){
			$this->error('订单不存在！');
		}
		$where[] = "site_id = '$site_id'";
		$where[] = "user_id = '$user_id'";
        //屏蔽删除的订单
        $where[] = "order_status <>".OS_ISDELETED;

        $OrderInfoCenter = D('OrderInfoCenter');  //会员中心的订单表
		$field = 'order_id,site_id,order_sn,order_status,shipping_status,postscript,pay_status,integral_money as integral,pay_id,pay_name,add_time,pay_time,goods_amount,bonus,shipping_fee,discount,money_paid,order_amount';
		$order = 'add_time desc';
        $data = $OrderInfoCenter->field($field)->where(implode(' and ',$where))->find();
		$data = $OrderInfoCenter->orderFormat($data, true, true, true);
        
		$this->success($data);
	}


    /**
     * 推荐商品
     * 推荐最多商品的类的商品
     */
    public function	recommendGoods(){

        $where = array();
        $where['site_id'] = C('SITE_ID');
        $where['user_id'] = $this->getUserId();
        if(empty($where['user_id'])){
            $this->error('您还没登录!');
        }
        $orderInfo =  D('OrderInfoCenter')->field('order_id')->where($where)->select();
        if(empty($orderInfo)){
            $this->error('用户还没有购物记录');
        }
        $orderIds = array();
        foreach($orderInfo as $k =>$v){
            $orderIds[] = $v['order_id'];
        }
        //获取已购买的商品
        $orderGoods =  D('OrderGoodsCenter')->field('goods_id,extension_code,goods_number')->where(array('site_id'=>$where['site_id'],'order_id'=>array('in',$orderIds),'extension_code'=>array('neq','package_goods')))->select();
        $package_goods = array();
        $goods = array();
        foreach($orderGoods as $kk => $vv){
            if($vv['extension_code'] == 'package_buy'){
                if(isset($package_goods[$vv['goods_id']])){
                    $package_goods[$vv['goods_id']] += $vv['goods_number'];
                }else{
                    $package_goods[$vv['goods_id']] = $vv['goods_number'];
                }
            }else{
                if(isset($goods[$vv['goods_id']])){
                    $goods[$vv['goods_id']] += $vv['goods_number'];
                }else{
                    $goods[$vv['goods_id']] = $vv['goods_number'];
                }
            }
        }
        //套装id转为商品id
        if(!empty($package_goods)){
            $goodsActivity_goods =  D('goodsActivity')->field('goods_id,act_id')->where(array('act_id'=>array('in',array_keys($package_goods))))->select();
            foreach($goodsActivity_goods as $k => $v){
                if(!empty($v['goods_id'])){
                    if(isset($goods[$v['goods_id']])){
                        $goods[$v['goods_id']] += $package_goods[$v['act_id']] ;
                    }else{
                        $goods[$v['goods_id']] = $package_goods[$v['act_id']] ;
                    }
                }
            }
        }
        //获取商品类,并进行商品数按商品类统计
        $cate_goods = D('goods') -> field('goods_id,cat_id')->where(array('goods_id'=>array('in',array_keys($goods)),'is_on_sale'=>1))->select();
        $cate = array();
        foreach($cate_goods as $k => $v){
            if(!empty($v['cat_id'])){
                if(isset($cate[$v['cat_id']])) {
                    $cate[$v['cat_id']] +=  $goods[$v['goods_id']];
                }else{
                    $cate[$v['cat_id']] =  $goods[$v['goods_id']];
                }
            }
        }
        $cate_max = max($cate);  //同类商品购买最多的
        $max_cates = array();
        foreach($cate as $n =>$value){
            if($value == $cate_max){  //一样多的都推荐
                $max_cates[] = $n;
            }
        }
        //按商品最多的类推荐商品
        /* 获取核心功效id*/
        $efficacy_id = D('goods_type gt')->join('__ATTRIBUTE__ ab on gt.cat_id = ab.cat_id')
            ->where(array('gt.cat_name'=>'化妆品','ab.attr_name'=>'核心功效'))
            ->field('attr_id')->find();
        $recommend_goods = D('goods g')->join('left join __GOODS_ATTR__ ga on g.goods_id = ga.goods_id')
            ->field('g.goods_id,goods_name,market_price,shop_price,goods_thumb,goods_img,original_img,virtual_sale,attr_value')
            ->where(array('is_on_sale'=>1,'is_show'=>1,'is_delete'=>0,'cat_id'=>array('in',$max_cates),'ga.attr_id'=>$efficacy_id['attr_id']))
            ->limit(10)->order('g.is_hot DESC,g.sort_order ASC,g.goods_id DESC')->select();
        foreach($recommend_goods as $k => $v){
            $recommend_goods[$k]['goods_thumb'] =  C('domain_source.img_domain'). $recommend_goods[$k]['goods_thumb'];
            $recommend_goods[$k]['goods_img'] =  C('domain_source.img_domain'). $recommend_goods[$k]['goods_img'];
            $recommend_goods[$k]['original_img'] =  C('domain_source.img_domain'). $recommend_goods[$k]['original_img'];
        }
        $this->success($recommend_goods);
    }


	/*
	*	检查当前是否登录
	*	@Author 9009123 (Lemonice)
	*	@return int [user_id]
	*/
	private function checkLogin(){
		$user_id = $this->getUserId();  //用户ID
		if($user_id <= 0){
			$this->error($this->not_login_msg);  //没登录
		}
		return $user_id;
	}
	
	/*
	*	获取当前登录用户ID
	*	@Author 9009123 (Lemonice)
	*	@return int [user_id]
	*/
	private function getUserId(){
		$user_id = $this->dbModel->getUser('user_id');  //用户ID
		$user_id = $user_id ? $user_id : 0;
		return $user_id;
	}
	
	/*
	*	暂时不使用本控制器默认方法，预留
	*	@Author 9009123 (Lemonice)
	*	@return exit & 404[not found]
	*/
	public function index(){
		send_http_status(404);
	}

    /**
     * 用户订单删除
     * @Author 9006758
     * @params orderId 订单id
     */
    public function delOrder(){
        $user_id = $this->getUserId();
        $order_id = I('request.orderId', 0, 'intval');

        //只能删除用户存在的订单
        if($user_id && $order_id){
            $where['user_id'] = $user_id;
            $where['order_id'] = $order_id;
            $res = D('OrderInfoCenter')->where($where)->setField('order_status', OS_ISDELETED);
            if($res){
                $this->success();
            }
        }
        $this->error('参数错误！');
    }
}