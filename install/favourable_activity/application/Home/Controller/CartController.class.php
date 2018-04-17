<?php
/**
 * ====================================
 * 购物车 控制器
 * ====================================
 * Author: 9006765
 * Date: 2016-06-28 14:32
 * ====================================
 * File: CartController.class.php
 * ====================================
 */

namespace Home\Controller;
use Common\Logic\Cart;
use Common\Controller\InitController;
use Common\Logic\FavourableActivityLogic;
use Common\Logic\GoodsActivityLogic;
use Common\Logic\GoodsLogic;


class CartController extends InitController
{
    private $logicCart = NULL;
    protected $user_id;

    public function __construct() {
        parent::__construct();
        $this->logicCart = new Cart();
        $this->user_id = is_null(session('user_id')) ? 0 : session('user_id');
        $this->filter_goods = C('place_order_filter.' . C('SITE_ID'));
    }

    /**
     * 商品加入购物车
     *
     */
    public function addGoodsToCart()
    {
        $goods_id = I('request.goods_id', 0, 'intval');  //商品的id或套装id（goods_id）
        $is_package = I('request.is_package', 0, 'intval');  //默认是单品,当值为1时，goods_id是套装id
        $goods_number = I('request.goods_number', 1, 'intval');  //商品数量
        $act_id = I('request.act_id', 0, 'intval');  //大于0时为活动商品
        $option = I('request.option', '', 'trim');  //值为select为勾选当前,不设置或值不为select为添加商品

        if ($goods_id <= 0) {
            $this->error('请指定加入购物车的商品');
        }
        $this->logicCart->setData('goods_id', $goods_id);
        $this->logicCart->setData('is_package', $is_package);
        $this->logicCart->setData('goods_number', $goods_number);
        $this->logicCart->setData('act_id', $act_id);
        $this->logicCart->setData('option', $option);
        $result = $this->logicCart->add();
        if ($result === false) {
            $error = $this->logicCart->getError();
            $this->error($error);
        }
        $this->success();
    }

    /**
     * 清除购物车勾选、对应商品 - 文章页面支付
     */
    public function cleanCart()
    {
        $goods_id = I('goods_id', '', 'trim');
        if ($goods_id == '') {
            $this->error('请传商品ID');
        }
        $this->logicCart->setData('goods_id', (strstr($goods_id, ',') ? explode(',', $goods_id) : array($goods_id)));
        $result = $this->logicCart->deleteGoodsId();
        $this->delAllGoods();  //把所有商品的勾选取消
        $this->success();
    }

    /**
     * 全不选/全反选
     */
    public function delAllGoods()
    {
        $this->logicCart->setData('select_type', 0);
        $result = $this->logicCart->selectAll();
        if ($result === false) {
            $error = $this->logicCart->getError();
            $this->error($error);
        }
        $this->success();
    }

    /**
     * 全选所有商品
     * @return bool
     */
    public function selectAllGoods()
    {
        $this->logicCart->setData('select_type', 1);
        $result = $this->logicCart->selectAll();
        if ($result === false) {
            $error = $this->logicCart->getError();
            $this->error($error);
        }
        $this->success();
    }

    /**
     * 显示购物车列表
     */
    public function showCart()
    {
        $pageSize = max(I('request.page_size', 10, 'intval'), 1);
        $page = max(I('request.page', 1, 'intval'), 1);
        $this->logicCart->setData('page_size', $pageSize);
        $this->logicCart->setData('page', $page);
        $this->logicCart->setData('is_show', 1);  //显示页面标志
        $this->logicCart->setData('get_image', 1);  //获取图片链接
        $data = $this->logicCart->getList();
        if ($data === false) {
            $error = $this->logicCart->getError();
            $this->error($error);
        }
        $this->ajaxReturn(array(
            'cart_goods_page_data' => $data['list'],
            'total_page' => $data['page_total'],
            'total_amount' => $data['amount'],
        ));
        exit;
    }

    /**
     * 减一个商品
     */
    public function mineOneGoods()
    {
        $goods_id = I('request.goods_id', 0, 'intval');  //商品的id或套装id（goods_id）
        $is_package = I('request.is_package', 0, 'intval');  //默认是单品,当值为1时，goods_id是套装id
        $act_id = I('request.act_id', 0, 'intval');  //大于0时为活动商品

        if ($goods_id <= 0) {
            $this->error('请指定加入购物车的商品');
        }
        $this->logicCart->setData('goods_id', $goods_id);
        $this->logicCart->setData('is_package', $is_package);
        $this->logicCart->setData('act_id', $act_id);
        $result = $this->logicCart->minus();
        if ($result === false) {
            $error = $this->logicCart->getError();
            $this->error($error);
        }
        $this->success();
    }


    /**
     * 去除勾选单个商品 ,当$_POST[real_del]==1 则是删除商品
     */
    public function delGoods()
    {
        $real_del = I('request.real_del', 0, 'intval');
        $rec_id = I('request.rec_id', 0, 'intval');

        if ($rec_id <= 0) {
            $this->error('请指定购物车的商品');
        }
        $this->logicCart->setData('real_del', $real_del);
        $this->logicCart->setData('rec_id', $rec_id);
        $result = $this->logicCart->delete();
        if ($result === false) {
            $error = $this->logicCart->getError();
            $this->error($error);
        }
        $this->success();
    }



    /**
     * 活动商品列表
     */
    public function activityList()
    {
        $only_gift = I('request.gift',0,'intval');
        $favourableActivityLogic = new FavourableActivityLogic();
        $actList = $favourableActivityLogic->getAddToCartActivityGoodsList($only_gift);
        //返回数据  - 格式 [{act_id:12,is_exchange_buy:0,is_free_gift:1,gift:[{name:'商品',price:'100',thumb:'a.jpg'}...],gift_package:[]}...]
        $this->ajaxReturn($actList);
    }
}