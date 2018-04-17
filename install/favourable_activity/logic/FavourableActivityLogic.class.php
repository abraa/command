<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/9/4
 * Time: 9:46
 */
namespace Common\Logic;

use Common\Extend\Time;

class   FavourableActivityLogic extends LogicData{
    const ACT_TYPE_DEFAULT = 0;                 //默认选购方式                                选购数量： 0 为无限制购买数量
    const ACT_TYPE_Increment = 1;                 //买一赠一                                   满足商品范围选购活动商品  活动商品数量不超过限定商品
    const ACT_TYPE_Equivalent = 2;                 //享受单品等价选购（受订购商品金额限制）    订购满：()元，可选购总价()的优惠品
    const ACT_TYPE_Limited = 3  ;                    //享受限量选购（受订购商品金额限制）         满(金额), 买(数量);
    const ACT_TYPE_Discount_Quantity = 4;                 //享受折扣选购（受订购商品数量限制）             满数量 折扣(0-10,10为不打折)
    const ACT_TYPE_Discount_Amount = 5;             //享受折扣选购（受订购商品金额限制）             满金额 折扣(0-10,10为不打折)
    const ACT_TYPE_Discount_Number = 6;                     //享受计件折扣或减免（受订购数量影响）            第(2)件，可享受(5)折扣(0-10,10为不打折,0为免费)
    const ACT_TYPE_Gifts = 7;                     //在线支付送赠品                   选购数量： 0 为无限制购买数量
    const ACT_TYPE_Physical = 8;                     //实物券优惠                      选购数量： 0 为无限制购买数量
    const ACT_TYPE_Full_Gift = 9;                     //满赠优惠                     选购数量： 0 为无限制购买数量
    const ACT_TYPE_Time_to_buy = 10;                     //限时抢购                     选购数量： 0 为无限制购买数量
    const ACT_TYPE_Featured = 11;                     //精选特卖                     选购数量： 0 为无限制购买数量
    const ACT_TYPE_Full_reduction = 13;                     //满立减（受订购商品总金额限制）                     选订购满：(100)元，可享受 (30)元减免


    protected $FavourableModel;                         //优惠活动模型
    protected $GoodsModel;                              //商品模型
    protected $GoodsActivityModel;                      //商品套装模型
    protected $CartModel;                               //购物车模型

    protected $GoodsActivityLogic = NULL;               //套装逻辑处理
    protected $GoodsLogic = NULL;                       //商品逻辑处理

    protected $favourableActivityList;                      //可用活动列表
    protected $result;                                   //当前活动处理结果
    protected $nowTime;                                   //当前GMT时间

    public function __construct($actList = "") {            //所有可用活动
        parent::__construct();
        $this->nowTime = Time::gmTime();                                //统一用一个时间
        if(empty($actList)){
            $this->favourableActivityList = $this->getFavourableActivityList();
        }else{
            $this->favourableActivityList = $actList;
        }

    }

    /**
     * 获取优惠活动模型
     * @return \FavourableModel|\Think\Model
     */
    protected function getCartModel(){
        if(!isset($this->CartModel)){
            $this->CartModel = D('Common/Home/Cart');
        }
        return $this->CartModel;
    }

    /**
     * 获取优惠活动模型
     * @return \FavourableModel|\Think\Model
     */
    protected function getFavourableModel(){
        if(!isset($this->FavourableModel)){
            $this->FavourableModel = D('Common/Home/FavourableActivity');
        }
        return $this->FavourableModel;
    }
    /**
     * 获取商品模型
     * @return \GoodsModel|\Think\Model
     */
    protected function getGoodsModel(){
        if(!isset($this->GoodsModel)){
           $this->GoodsModel = D('Common/Home/Goods');
        }
        return $this->GoodsModel;
    }

    /**
     * 获取套装模型
     * @return \GoodsActivityModel|\Think\Model
     */
    protected function getGoodsActivityModel(){
        if(!isset($this->GoodsActivityModel)){
            $this->GoodsActivityModel = D('Common/Home/GoodsActivity');
        }
        return $this->GoodsActivityModel;
    }

    /**
     * 获取套装商品处理类
     * @return GoodsActivityLogic|null
     */
    protected function getGoodsActivityLogic(){
        if(!isset($this->GoodsActivityLogic)){
            $this->GoodsActivityLogic = new GoodsActivityLogic();
        }
        return $this->GoodsActivityLogic;
    }
    /**
     * 获取商品处理类
     * @return GoodsLogic|null
     */
    protected function getGoodsLogic(){
        if(!isset($this->GoodsLogic)){
            $this->GoodsLogic = new GoodsLogic();
        }
        return $this->GoodsLogic;
    }

    /**
     * 获取是否勾选 ，此优惠活动的商品总价格也将用于优惠的价格限制计算
     * @param int $act_id    活动id
     * @return mixed    1参与
     */
    protected function getIsJoinAmount($act_id){
        if(empty($this->favourableActivityList[$act_id])){
            $result = $this->getFavourableModel()->getIsJoinAmount();
        }else{
            $result =  $this->favourableActivityList[$act_id]['is_join_amount'];
        }
        return $result;
    }

    /**
     * 活动当前可用活动列表
     * @return mixed
     */
    public function getFavourableActivityList(){
        if(empty($this->favourableActivityList)){
            $this->favourableActivityList = $this->getFavourableModel()->getAllActivity($this->nowTime);
        }
        return $this->favourableActivityList;
    }

    /**
     * 活动当前可用指定活动         --不可用的活动无返回
     * @param $act_id    活动id
     * @return mixed
     */
    public function getFavourableActivity($act_id){
        if(empty($this->favourableActivityList)){
            $this->favourableActivityList = $this->getFavourableModel()->getAllActivity($this->nowTime);
        }
        return isset($this->favourableActivityList[$act_id]) ? $this->favourableActivityList[$act_id] : null;
    }

    /**
     * 最新活动是否可用
     * @return mixed
     */
    public function checkFavourableActivityStarted(){
            return $this->getFavourableModel()->getActivityStarted($this->nowTime);
    }

    /**
     * 检查优惠活动是否满足条件 (入口)        //添加之前
     * @param $act_id
     * @param $goods_id
     * @param bool $is_package
     * @param int $goods_number
     * @param array $cart_list                  //需要检查的购物车列表       注:如果是一件一件添加　cart_list只有添加前的数据 当前数据和还未添加的数据不在其中
     * @return bool
     * @throws \Exception
     */
    public function checkFavourableActivity($act_id , $goods_id , $is_package = false ,$goods_number = 1,$cart_list = array()){
        // 1. 根据act_id查询活动信息
        if(empty($this->favourableActivityList[$act_id])){
            $actInfo = $this->getFavourableModel()->getActivity($act_id);
        }else{
            $actInfo =$this->favourableActivityList[$act_id];
        }
        if(empty($actInfo)){
            $this->setError("找不到指定活动".$act_id);
            return false;
        }
        $this->setDatas();                                       //初始化当前Data -- 防止后续处理数据时上一次checkAct数据残留
            //检查通用设定
        if(!$this->checkCommon($actInfo , $goods_id , $is_package , $goods_number ,  $cart_list)){
            return false;
        }
        // 2. 根据活动条件使用不同函数处理
        switch($actInfo["act_type"]){                      //活动类型
            case self::ACT_TYPE_DEFAULT:                               //默认选购方式
                $result = $this->checkActTypeDefault($actInfo ,$goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Increment:                             //买一赠一
                $result = $this->checkActTypeIncrement($actInfo ,$goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Equivalent:                             //享受单品等价选购（受订购商品金额限制）
                $result = $this->checkActTypeEquivalent($actInfo ,$goods_id ,$is_package  , $goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Limited:                                 //享受限量选购（受订购商品金额限制）
                $result = $this->checkActTypeLimited($actInfo ,$goods_id ,$is_package  , $goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Discount_Quantity:                        //享受折扣选购（受订购商品数量限制）(购物车满数量再买才打折,其他正价)
                $result = $this->checkActTypeDiscountQuantity($actInfo ,$goods_id ,$is_package  , $goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Discount_Amount:                       //享受折扣选购（受订购商品金额限制）
                $result = $this->checkActTypeDiscountAmount($actInfo ,$goods_id ,$is_package  , $goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Discount_Number:                           //享受计件折扣或减免（受订购数量影响）(当前商品数量达第几件才打折,其他正价)
                $result = $this->checkActTypeDiscountNumber($actInfo ,$goods_id ,$is_package  , $goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Gifts:                                     //在线支付送赠品
                $result =  $this->checkActTypeDefault($actInfo ,$goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Physical:                                  //实物券优惠
                $result =  $this->checkActTypeDefault($actInfo ,$goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Full_Gift:                                  ///满赠优惠
                $result =  $this->checkActTypeDefault($actInfo ,$goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Time_to_buy:                                  ///限时抢购
                $result =  $this->checkActTypeDefault($actInfo ,$goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Featured:                                     // //精选特卖
                $result =  $this->checkActTypeDefault($actInfo ,$goods_number , $cart_list);
                break;
            case self::ACT_TYPE_Full_reduction:                               //满立减（受订购商品总金额限制）
                $result =  $this->checkActTypeFullReduction($actInfo ,$goods_id ,$is_package  , $goods_number , $cart_list);
                break;
            default:
                $this->setError("未知活动类型");
                return false;

        }
        if($result){                                                                //  通过
            $buy_discount = $this->getData("buy_discount");                         //获取是否有折扣
            $goods_result =array();                                                 // 处理结果
            $shipping_free = (0 == $actInfo['shipping_free']) ? array() : explode(",",$actInfo['shipping_free']);       //是否包邮 (1.在线支付 2.货到付款)
            if($buy_discount){
                foreach($buy_discount as $key=>$discount){                            //遍历多个数量 获取每个商品的折扣
                    $price = $this->getGiftPrice($actInfo,$goods_id,$is_package,$discount);     //当前商品价格
                    $goods_result[$key] = array('act_id'=>$act_id,'goods_id'=>$goods_id,"is_package"=>$is_package,'price'=>$price,'discount'=>$discount,'shipping_free'=>$shipping_free);
                }
            }else{
                $price = $this->getGiftPrice($actInfo,$goods_id,$is_package);                //获取商品售卖价格
                $goods_result = array_fill(0,$goods_number, array('act_id'=>$act_id,'goods_id'=>$goods_id,"is_package"=>$is_package,'price'=>$price,'shipping_free'=>$shipping_free));
            }
            $this->setData('result',$goods_result);
        }
        // 5. 返回结果
        return $result;
    }


    /**
     * 检查通用设置
     * @param array $actInfo    活动信息
     * @param int $goods_id     商品id
     * @param bool $is_package  是否套装
     * @param int $goods_number  商品数量
     * @param array $cart_list  购物车列表
     * @return bool
     */
    protected function checkCommon($actInfo , $goods_id , $is_package = false ,$goods_number = 1 , $cart_list = array()){
        if(empty($actInfo)) return false;
        //1. 判断活动是否开始 -- 不在有效时间直接结束
        $nowTime = isset($this->nowTime) ? $this->nowTime : Time::gmTime();
        if($nowTime<$actInfo['start_time']){
            $this->setError($actInfo['act_name']."活动还没开始");
            return false;
        }
        if($nowTime>$actInfo['end_time']){
            $this->setError($actInfo['act_name']."活动已经结束");
            return false;
        }
        //2. 判断会员                                -- 不能参与活动直接结束
        $user_rank = explode(",",$actInfo['user_rank']);
        $user_id = session('user_id');
        $userInfo = session("userInfo");
        if(!in_array("0",$user_rank) && empty($user_id)){           //没有登录并且未勾选非会员 -- 0 未勾选
            $this->setError("没有登录不能参加活动");
            return false;
        }
        $user_level = $userInfo[$user_id]['total_points'] >0 ? 2 : 1;   //积分大于0 认为是vip 否则是注册会员
        if(isset($user_id) && !in_array($user_level,$user_rank)){   //登录但当前会员等级不允许参加  --  1 | 2 未勾选
            $this->setError("当前会员等级不能参加活动");
            return false;
        }

        //3. 判断是否有与该活动同时进行的活动       -- 不能同时进行直接结束
        if(!$this->checkConflictAct($actInfo , $cart_list)){
           return false;
        }
        //4. 判断必须先购买含有指定字符的正价商品     -- 如果商品有buylimit字段 判断商品的buylimit
        if(!$this->checkContainStr($actInfo , $goods_id ,$is_package , $cart_list)){
            return false;
        }
        //5. 判断优惠范围                             -- 购物车必须有指定商品
        if(!$this->checkActRange($actInfo ,  $cart_list)){
            return false;
        }
        //6. 判断金额下限                             --如果商品有金额下限判断商品的金额下限
        if(!$this->checkMinAmount($actInfo ,$goods_id ,$is_package ,  $cart_list)){
            return false;
        }
        //7. 判断金额上限                             --如果商品有金额上限判断商品的金额上限
        if(!$this->checkMaxAmount($actInfo ,$goods_id ,$is_package ,  $cart_list)){
            return false;
        }
        //8. 判断库存
        if(!$this->checkStockLimited($actInfo , $goods_id , $is_package , $goods_number , $cart_list)){
            return false;
        }
        return true;
    }


    /**
     * 检查优惠活动是否没有与该活动同时进行的活动     conflict_act
     * @param array $actInfo              优惠活动信息
     * @param array $cart_list      购物车列表
     * @return bool                 true 不存在不能同时进行的活动
     */
    protected function checkConflictAct($actInfo , $cart_list = array()){
        if(empty($actInfo)) return false;
        $act_list = array();                        //购物车中活动id列表
        foreach($cart_list as  $v){
            if(0 < $v['is_gift']  ){
                $act_list[$v['is_gift']] = $v['goods_name'];             //为了获取错误时提示的购物车中商品名称
            }
        }
        $conflict_act  = unserialize($actInfo['conflict_act']);         //不能同时进行活动id
        foreach($conflict_act as $val){
            if(in_array($val['act_id'],array_keys($act_list))){
                $this->setError("购物车中存在不能同时进行活动商品".$act_list[$val['act_id']]);
                return false;
            }
        }
        return true;

    }


    /**
     * 检查是否存在必须购买含有指定字符的正价商品|限制商品   contain_str | buylimit
     * @param array $actInfo              优惠活动信息
     * @param string $goods_id      商品id
     * @param bool $is_package      是否套装
     * @param array $cart_list      购物车列表
     * @return bool                 true 存在指定字符商品
     */
    protected function checkContainStr($actInfo ,$goods_id = "" , $is_package = false ,  $cart_list = array()){
        if(empty($actInfo)) return false;
        $contain_str = isset($actInfo['contain_str']) ?  $actInfo['contain_str'] : "";
        if($goodsInfo = $this->getActivityGoodsInfo($actInfo,$goods_id,$is_package)){
            $contain_str = empty($goodsInfo["buylimit"]) ? $contain_str :  $goodsInfo["buylimit"];       //如果有商品id判断活动商品是否限定字段 buylimit
            if("" == $contain_str) return true;       //没有限定返回成功
            foreach($cart_list as $v){
                if(0 == $v['is_gift'] && false !== strpos($v['goods_name'],$contain_str)){
                    return true;                          //有"like"指定名称商品 并且不属于活动商品
                }
            }
            $this->setError("购物车不含有当前优惠活动商品:".$goodsInfo['name']."指定名称商品");
        }
        return false;
    }

    /**
     *  检查是否存在优惠范围         act_range
     * @param array $actInfo
     * @param array $cart_list
     * @return bool                 true 存在指定范围商品
     */
    protected function checkActRange($actInfo , $cart_list = array()){
        if(empty($actInfo)) return false;
        $act_range_ext = array();
        if (!empty($actInfo['act_range_ext'])) {
            $act_range_ext = explode(",", $actInfo['act_range_ext']);
        }
        switch($actInfo['act_range']){
            case 1:                                     //全部套装
                foreach ($cart_list as $goods) {
                    if (0 == strcmp("package_buy",$goods['extension_code'])) {    //存在套装
                        return true;
                    }
                }
                break;
            case 2:                                       //以下分类
                foreach ($cart_list as $goods) {
                    $extCat = $this->getCartModel()->getCartGoodsExtCat($goods['rec_id'],$cart_list);       //获取当前商品所属分类
                    $intersect = array_intersect($act_range_ext,$extCat);                               //取交集
                    if (isset($extCat) && !empty($intersect)) {               //存在分类
                        return true;
                    }
                }
                break;
            case 3:                                         //以下商品
                foreach ($cart_list as $goods) {
                    if (0 <> strcmp("package_buy",$goods['extension_code']) && in_array($goods['goods_id'],$act_range_ext)) {               //存在商品
                        return true;
                    }
                }
                break;
            case 4:                                         //以下套装
                foreach ($cart_list as $goods) {
                    if (0 == strcmp("package_buy",$goods['extension_code']) && in_array($goods['goods_id'],$act_range_ext)) {               //存在套装
                        return true;
                    }
                }
                break;
            default:
                return true;
        }
        $this->setError("购物车不含有当前优惠活动:".$actInfo['act_name']."指定范围商品");
        return false;
    }


    /**
     * 检查是否满足活动最小金额
     * @param array $actInfo        活动信息
     * @param string $goods_id      商品id
     * @param bool $is_package      是否套装
     * @param array $cart_list      购物车列表
     * @return bool                 true 满足活动最小金额
     */
    protected function checkMinAmount($actInfo ,$goods_id = "" , $is_package = false ,  $cart_list = array()){
        if(empty($actInfo)) return false;
        $min_amount = $actInfo['min_amount'];
        //获取活动商品信息
        if($goodsInfo = $this->getActivityGoodsInfo($actInfo,$goods_id,$is_package)){
            //如果商品存在下限金额则判断商品的下限金额
            $min_amount = is_numeric($goodsInfo['pmin']) ?  $goodsInfo['pmin'] : $min_amount ;
            if(0 == $min_amount){                 //没有设定返回成功
                return true;
            }
            $amount = 0;
            foreach($cart_list as $v){          //统计购物车金额
                if(!$v['is_gift'] || $this->getIsJoinAmount($v['is_gift']) ){              // 不是优惠活动商品 || 优惠活动允许商品参与统计
                    $amount += $v['goods_price'] * $v['goods_number'];
                }
            }
            if($amount < $min_amount){
                $this->setError("购物车商品未到达优惠活动商品:".$goodsInfo['name']."指定下限金额");
                return false;
            }else{
                return true;
            }
        }
        return false;
    }

    /**
     * 检查是否满足活动最大金额
     * @param array $actInfo        活动信息
     * @param string $goods_id      商品id
     * @param bool $is_package      是否套装
     * @param array $cart_list      购物车列表
     * @return bool     true 不超过最大金额
     */
    protected function checkMaxAmount($actInfo ,$goods_id = "" , $is_package = false ,  $cart_list = array()){
        if(empty($actInfo)) return false;
        $max_amount = $actInfo['max_amount'];
        //获取活动商品信息
        if($goodsInfo = $this->getActivityGoodsInfo($actInfo,$goods_id,$is_package)){
            //如果商品存在上限金额则判断商品的上限金额
            $max_amount = is_numeric($goodsInfo['pmax']) ? $goodsInfo['pmax'] : $max_amount;
            if(0 == $max_amount){                 //没有设定返回成功
                return true;
            }
            $amount = 0;
            foreach($cart_list as $v){          //统计购物车金额
                if(!$v['is_gift'] || $this->getIsJoinAmount($v['is_gift']) ){              // 不是优惠活动商品 || 优惠活动允许商品参与统计
                    $amount += $v['goods_price'] * $v['goods_number'];
                }
            }
            if($amount > $max_amount){
                $this->setError("购物车商品超过优惠活动商品:".$goodsInfo['name']."指定上限金额");
                return false;
            }else{
                return true;
            }
        }
        return false;
    }


    /**
     * 判断库存
     * @param $actInfo
     * @param string $goods_id
     * @param bool $is_package
     * @param int $goods_number
     * @param array $cart_list
     * @return bool
     */
    protected function checkStockLimited($actInfo , $goods_id = "" , $is_package = false , $goods_number = 1 , $cart_list = array()){
        if($actInfo['stock'] == 1){
            if($is_package){
                $stock_limited = $this->getGoodsActivityModel()->getGoodsStock($goods_id);              //检查库存数量
                $extension_code = "package_buy";
            }else{
                $stock_limited = $this->getGoodsModel()->getGoodsStock($goods_id);
                $extension_code = "";
            }
            //检查当前商品数量
            foreach($cart_list as $v){
                if($actInfo['act_id'] == $v['is_gift'] && $goods_id == $v['goods_id'] && $extension_code == $v['extension_code']){
                    $goods_number += $v['goods_number'];
                }
            }
            //判断是否满足库存
            if($stock_limited['goods_id'] === null ){
                $goods_name = $this->getActivityGoodsName($actInfo,$goods_id,$is_package);
                $this->setError("当前活动商品".$goods_name."没有设定库存");
                return false;
            }elseif($stock_limited['goods_id'] === 0){
                $goods_name = $this->getActivityGoodsName($actInfo,$goods_id,$is_package);
                $this->setError("当前活动商品".$goods_name."已抢空");
                return false;
            }elseif($stock_limited['goods_id'] < $goods_number){
                $goods_name = $this->getActivityGoodsName($actInfo,$goods_id,$is_package);
                $this->setError("当前活动商品".$goods_name."库存不足");
                return false;
            }
        }
        return true;
    }


    /**
     * 默认选购方式            --限定选购数量
     * @param $actInfo
     * @param int $goods_number
     * @param array $cart_list
     * @return bool
     */
    protected function checkActTypeDefault($actInfo , $goods_number = 1 ,$cart_list = array()){

        if(empty($actInfo) ) return false;
        if(empty($actInfo['act_type_ext'])) return true;        // 0无限制
        foreach($cart_list as $v){                  //统计购物车中本活动商品数量
          if($actInfo['act_id'] == $v['is_gift'])  {
              $goods_number += $v['goods_number'];
          }
        }
        if($goods_number > $actInfo['act_type_ext'] ){
            $this->setError("商品数量达到".$actInfo['act_name']."活动限制".$actInfo['act_type_ext']);
            return false;
        }
        return true;
    }

    /**
     *  买一赠一              --  满足商品范围选购活动商品  活动商品数量不超过限定商品数量   act_range_ext                ? 如果是商品自增的话需要修改这里
     * @param array $actInfo
     * @param int $goods_number         商品数量
     * @param array $cart_list
     * @return bool
     */
    protected function checkActTypeIncrement($actInfo ,$goods_number = 1  , $cart_list = array()){

        if( empty($actInfo) ) return false;
        if (1 < $actInfo['act_range'] && empty($actInfo['act_range_ext'])) {            //判断是否存在优惠范围
            $this->setError("当前优惠活动:".$actInfo['act_name']."商品范围为空");
            return false;
        }
        $act_range_ext = array();
        if (!empty($actInfo['act_range_ext'])) {
            $act_range_ext = explode(",", $actInfo['act_range_ext']);
        }
        $num = 0;                              //范围商品数量
        foreach($cart_list as $v){                      //统计购物车中本活动商品数量
            if($actInfo['act_id'] == $v['is_gift']) {       //检查当前活动商品数量
                $goods_number += $v['goods_number'];
                continue;
            }
            switch($actInfo['act_range']){                  //统计指定范围商品数量
                case 0 :
                    if (0 <> strcmp("package_buy",$v['extension_code'])) {    //存在商品
                        $num += $v['goods_number'];
                    }
                    break;
                case 1 :
                    if (0 == strcmp("package_buy",$v['extension_code'])) {    //存在套装
                        $num += $v['goods_number'];
                    }
                    break;
                case 2 :                                    //以下分类
                    $extCat = $this->getCartModel()->getCartGoodsExtCat($v['rec_id'],$cart_list);       //获取当前商品所属分类
                    $intersect = array_intersect($act_range_ext,$extCat);       //取交集
                    if (isset($extCat) && !empty($intersect)) {               //存在分类
                        $num += $v['goods_number'];
                    }
                    break;
                case 3 :                                    //以下商品
                    if (0 <> strcmp("package_buy",$v['extension_code']) && in_array($v['goods_id'],$act_range_ext)) {               //存在商品
                        $num += $v['goods_number'];
                    }
                    break;
                case 4 :                                    //以下套装
                    if (0 == strcmp("package_buy",$v['extension_code']) && in_array($v['goods_id'],$act_range_ext)) {               //存在套装
                        $num += $v['goods_number'];
                    }
                    break;
                default :
                   return false;
            }
        }
        if($goods_number > $num ){
            $this->setError($actInfo['act_name']."活动赠品数量超过限定商品数量".$num);
            return false;
        }
        return true;
    }


    /**
     * 享受单品等价选购（受订购商品金额限制）      --  订购满：()元，可选购总价()的优惠品
     * @param array $actInfo
     * @param int $goods_id
     * @param bool $is_package
     * @param int $goods_number
     * @param array $cart_list
     * @return bool
     */
    protected function checkActTypeEquivalent($actInfo , $goods_id ,$is_package = false , $goods_number = 1  , $cart_list = array()){

        if(empty($actInfo)) return false;
        $act_type_ext = $this->getFavourableModel()->getActTypeExt($actInfo['act_id'],$actInfo);
        if(empty($act_type_ext)){
            $this->setError($actInfo['act_name']."活动没有设置金额");
            return false;
        }
        $total_price = 0;                       //购物车总金额
        $goods_price = 0;                    //当前活动商品总金额
        $price = $this->getGiftPrice($actInfo , $goods_id,$is_package);         //获取当前活动商品的价格
        if(false === $price){
            return false;
        }
        foreach($cart_list as $v){                                  //统计购物车金额
            if($v['is_gift']==$actInfo['act_id']){                                      //活动商品本身总金额统计
                $goods_price += round($v['goods_price'] * $v['goods_number'],2);
            }
            if(!$v['is_gift'] || $this->getIsJoinAmount($v['is_gift']) ){              // 不是优惠活动商品 || 优惠活动允许商品参与统计
                $total_price += round($v['goods_price'] * $v['goods_number'],2);
            }
        }
        for($i = 1; $i <= $goods_number ; $i++){            //如果goods_number > 1 一个一个判断 失败全部视为失败
            $t_goods_price = $goods_price + ($price * $i);                     //加入 $i 件商品后的金额
            if($actInfo['is_join_amount']){                                   //如果商品本身可以参与总金额统计
                $t_total_price = $total_price + ($price * ($i-1));               // 总金额 + 前几件商品的金额
            }
            $result = false;
            foreach($act_type_ext as $fun_price => $buy_price){             //检查是否满足活动需求        -- 满足一条即通过
                if($fun_price <= $t_total_price  && (empty($buy_price)|| $t_goods_price < $buy_price) ){                      //总金额通过
                    $result = true;
                    break;
                }
            }
            if(!$result){                                                   //任何一件无法通过则失败
                $goods_name = $this->getActivityGoodsName($actInfo ,$goods_id,$is_package);
                $this->setError("订单金额未达到".$goods_name."总金额限制或者超过活动可购买金额限制");
                return false;
            }
        }
        return true;
    }


    /**
     *  享受限量选购（受订购商品金额限制）         --  满(金额), 买(数量);
     * @param array $actInfo
     * @param int $goods_id
     * @param bool $is_package
     * @param int $goods_number
     * @param array $cart_list
     * @return bool
     */
    protected function checkActTypeLimited($actInfo , $goods_id ,$is_package = false , $goods_number = 1  , $cart_list = array()){

        if(empty($actInfo)) return false;
        $act_type_ext = $this->getFavourableModel()->getActTypeExt($actInfo['act_id'],$actInfo);
        if(empty($act_type_ext)){
            $this->setError($actInfo['act_name']."活动没有设置金额");
            return false;
        }
        $price = $this->getGiftPrice($actInfo , $goods_id,$is_package);         //获取当前活动商品的价格
        if(false === $price){
            return false;
        }
        $total_price = 0;                                          //购物车总金额
        $total_number = 0;                                          //购物车中本活动商品数量
        foreach($cart_list as $v){                                  //统计购物车金额
            if($v['is_gift']==$actInfo['act_id']){                                      //活动商品总数量
                $total_number += $v['goods_number'];
            }
            if(!$v['is_gift'] || $this->getIsJoinAmount($v['is_gift']) ){              // 不是优惠活动商品 || 优惠活动允许商品参与统计
                $total_price += round($v['goods_price'] * $v['goods_number'],2);
            }
        }

        for($i = 1; $i <= $goods_number ; $i++){            //如果goods_number > 1 一个一个判断 失败全部视为失败
            $total_number++;                                //购买活动商品 +1
            if($actInfo['is_join_amount']){                                   //如果商品本身可以参与总金额统计
                $t_total_price = $total_price + ($price * ($i-1));               // 总金额 + 前几件商品的金额
            }
            $result = false;
            foreach($act_type_ext as $fun_price => $buy_number){             //检查是否满足活动需求        -- 满足一条即通过
                if($fun_price <= $t_total_price  && (empty($buy_price)|| $total_number <= $buy_number) ){        //通过   0 视为无限制
                    $result = true;
                    break;
                }
            }
            if(!$result){                                                   //任何一件无法通过则失败
                $goods_name = $this->getActivityGoodsName($actInfo ,$goods_id,$is_package);
                $this->setError("订单金额未达到".$goods_name."总金额限制或者超过可购买数量限制");
                return false;
            }
        }
        return true;
    }


    /**
     *  享受折扣选购（受订购商品数量限制）             满数量 折扣(0-10,10为不打折)   折扣不提示失败
     * @param $actInfo
     * @param $goods_id
     * @param bool $is_package
     * @param int $goods_number
     * @param array $cart_list
     * @return bool                                     $this->setData('buy_discount',折扣);
     */
    protected function checkActTypeDiscountQuantity($actInfo , $goods_id ,$is_package = false , $goods_number = 1  , $cart_list = array()){

        if(empty($actInfo)) return false;
        $act_type_ext = $this->getFavourableModel()->getActTypeExt($actInfo['act_id'],$actInfo);
        if(empty($act_type_ext)){
            $this->setError($actInfo['act_name']."活动没有设置数量");
            return false;
        }
        $total_number = 0;
        foreach($cart_list as $v){                                           //统计购物车商品数量
                $total_number += $v['goods_number'];                        //包含优惠活动商品
        }
        $discountArr = array();                     //每件商品的折扣数组
        for($i=1;$i<=$goods_number;$i++){
            $discount = 10;                             //默认不打折
            foreach($act_type_ext as $fun_number => $buy_discount){             //检查是否满足活动需求        -- 满足一条即通过
                if($fun_number <= $total_number  ){        //通过   0 视为无限制
                    $discount = $discount < $buy_discount ? $discount : $buy_discount ;                    //取出符合条件的最大折扣 -- 最小值
                }
            }
            $total_number++;                                                //总数量加上当前商品 +1
            $discountArr[$i] = $discount;                                   //每一件商品的折扣
        }
        $this->setData("buy_discount",$discountArr);
        return true;                                                //折扣不提示失败使用原价
    }

    /**
     *  享受折扣选购（受订购商品金额限制）             满金额 折扣(0-10,10为不打折)           折扣不提示失败
     * @param $actInfo
     * @param $goods_id
     * @param bool $is_package
     * @param int $goods_number
     * @param array $cart_list
     * @return bool                                     $this->setData('buy_discount',折扣);
     */
    protected function checkActTypeDiscountAmount($actInfo , $goods_id ,$is_package = false , $goods_number = 1  , $cart_list = array()){

        if(empty($actInfo)) return false;
        $act_type_ext = $this->getFavourableModel()->getActTypeExt($actInfo['act_id'],$actInfo);
        if(empty($act_type_ext)){
            $this->setError($actInfo['act_name']."活动没有设置金额");
            return false;
        }
        $total_price = 0;
        foreach($cart_list as $v){                                           //统计购物车商品金额
            if(!$v['is_gift'] || $this->getIsJoinAmount($v['is_gift']) ){              // 不是优惠活动商品 || 优惠活动允许商品参与统计
                $total_price += round($v['goods_price'] * $v['goods_number'],2);
            }
        }
        $discountArr = array();                     //每件商品的折扣数组
        for($i=1;$i<=$goods_number;$i++){
            $discount = 10;                             //默认不打折
            foreach($act_type_ext as $fun_price => $buy_discount){             //检查是否满足活动需求        -- 满足一条即通过
                if($fun_price <= $total_price  ){                    //通过   0 视为无限制
                    $discount = $discount < $buy_discount ? $discount : $buy_discount ;                    //取出符合条件的最大折扣 -- 最小值
                }
            }
            if($actInfo['is_join_amount']){                         //goods_number >1 并且 活动参与总金额统计 加上这一件的总金额参与计算
                $price = $this->getGiftPrice($actInfo,$goods_id,$is_package,$discount);
                if(false === $price)    return false;           //商品金额获取失败直接结束
                $total_price = $total_price + $price;                               // 总金额 + 当前商品的金额
            }
            $discountArr[$i] = $discount;                                   //每一件商品的折扣
        }
        $this->setData("buy_discount",$discountArr);
        return true;                                                        //折扣不提示失败使用原价
    }

    /**
     *  享受计件折扣或减免（受订购数量影响）            第(2)件，可享受(5)折扣(0-10,10为不打折,0为免费)           折扣不提示失败
     * @param $actInfo
     * @param $goods_id
     * @param bool $is_package
     * @param int $goods_number
     * @param array $cart_list
     * @return bool                                     $this->setData('buy_discount',折扣);
     */
    protected function checkActTypeDiscountNumber($actInfo , $goods_id ,$is_package = false , $goods_number = 1  , $cart_list = array()){

        if(empty($actInfo)) return false;
        $act_type_ext = $this->getFavourableModel()->getActTypeExt($actInfo['act_id'],$actInfo);
        if(empty($act_type_ext)){
            $this->setError($actInfo['act_name']."活动没有设置数量");
            return false;
        }
        $total_number = 0;
        foreach($cart_list as $v){                                           //统计购物车商品
            if($actInfo['act_id'] == $v['is_gift'])  {                          //当前活动商品
                $total_number += $v['goods_number'];
            }
        }
        $discountArr = array();                     //每件商品的折扣数组
        for($i=1;$i<=$goods_number;$i++){
            $total_number++;                            //一件一件加入判断
            $discount = 10;                             //默认不打折
            foreach($act_type_ext as $fun_number => $buy_discount){             //检查是否满足活动需求        -- 满足一条即通过
                if(empty($fun_number) || $fun_number == $total_number ){                             //通过   0 视为无限制
                    $discount = $discount < $buy_discount ? $discount : $buy_discount ;                    //取出符合条件的最大折扣 -- 最小值
                }
            }
            $discountArr[$i] = $discount;                                   //每一件商品的折扣
        }
        $this->setData("buy_discount",$discountArr);
        return true;                                                        //折扣不提示失败使用原价
    }

    /**
     *  满立减（受订购商品总金额限制）                     选订购满：(100)元，可享受 (30)元减免        折扣不提示失败
     * @param $actInfo
     * @param $goods_id
     * @param bool $is_package
     * @param int $goods_number
     * @param array $cart_list
     * @return bool                                     $this->setData('buy_discount',array("reduction"=>减免金额));
     */
    protected function checkActTypeFullReduction($actInfo , $goods_id ,$is_package = false , $goods_number = 1  , $cart_list = array()){

        if(empty($actInfo)) return false;
        $act_type_ext = $this->getFavourableModel()->getActTypeExt($actInfo['act_id'],$actInfo);
        if(empty($act_type_ext)){
            $this->setError($actInfo['act_name']."活动没有设置金额");
            return false;
        }
        $total_price = 0;
        foreach($cart_list as $v){                                           //统计购物车商品金额
            if(!$v['is_gift'] || $this->getIsJoinAmount($v['is_gift']) ){              // 不是优惠活动商品 || 优惠活动允许商品参与统计
                $total_price += round($v['goods_price'] * $v['goods_number'],2);
            }
        }
        $discountArr = array();                     //每件商品的折扣数组
        for($i=1;$i<=$goods_number;$i++){
            $reduction = 0;                             //默认不减
            foreach($act_type_ext as $fun_price => $buy_reduction){             //检查是否满足活动需求        -- 满足一条即通过
                if($fun_price <= $total_price  ){                    //通过   0 视为无限制
                    $reduction = $reduction > $buy_reduction ? $reduction : $buy_reduction ;                    //取出符合条件的最大减免 -- 最大值
                }
            }
            $reductionArr = array("reduction",$reduction);
            if($actInfo['is_join_amount']){                         //goods_number >1 并且 活动参与总金额统计 加上这一件的总金额参与计算
                $price = $this->getGiftPrice($actInfo,$goods_id,$is_package,$reductionArr);
                if(false === $price)    return false;           //商品金额获取失败直接结束
                $total_price = $total_price + $price;                               // 总金额 + 当前商品的金额
            }
            $discountArr[$i] = $reductionArr;                                   //每一件商品的折扣
        }
        $this->setData("buy_discount",$discountArr);
        return true;                                                        //折扣不提示失败使用原价
    }

    /**
     * 检查范围赠品价格    如果有设置 优先于gift|gift_package直接设定价格
     * @param $actInfo
     * @param string $goods_id
     * @param bool $is_package
     * @param int $price
     * @return bool
     */
    protected function checkGiftRange($actInfo ,$goods_id = "" , $is_package = false , &$price = 0){
        if(empty($actInfo['gift_range'])){  //判断是否选择范围 gift_range
            return true;
        }
        //获取商品原价
        if($is_package){                                            //是套装
            $goods = $this->getGoodsActivityModel()->getPackageInfo(0,$goods_id,false);
            if(empty($goods)){
                $this->setError("该套装不存在");
                return false;
            }
            $shop_price = $goods['package_price'];
            if($actInfo['gift_range'] == 1){                    //判断gift_range 是全部单品还是全部产品          1单品 | 2 产品
                $price = empty($price) ? $shop_price : $price;                            //如果gift_range是单品 并且商品是套装时 直接结束 使用原价
                return true;
            }
        }else{                                                      //是单品
            $goods = $this->getGoodsModel()->getGoodsInfo($goods_id);
            if(empty($goods)){
                $this->setError("该商品不存在");
                return false;
            }
            $shop_price = $goods['shop_price'];
        }
        //判断折扣价格                              - gift_range_price(直接输入数字则为全部商品售价，输入10%则所有商品卖一折)
        if (false !== strpos($actInfo['gift_range_price'],'%'))        //判断是否折扣     10%
        {
            $gift_range_price = str_replace('%','',$actInfo['gift_range_price']);
            $gift_range_price = round($shop_price / 100 * $gift_range_price , 2) ;
        }else if(is_numeric($actInfo['gift_range_price'])){         //判断是否直接数字金额
            $gift_range_price = $actInfo['gift_range_price'];
        }else{                                                          //其他字符不做处理
            return true;
        }
        $price = $gift_range_price;                                     //返回折扣后价格
        return true;
    }


    /**
     * 获取活动商品售卖价格                   --  直接返回价格
     * @param $actInfo
     * @param string $goods_id
     * @param bool $is_package
     * @param mixed $discount --  0 - 10 0免费 10 不打折         如果是数组条件在这里说明  array("reduction"=>'减免金额', )
     * @return mixed                            false|价格
     * @throws \Exception                       --  有设定key却没有做key对应处理
     */
    protected function getGiftPrice($actInfo ,$goods_id = "" , $is_package = false ,$discount = false){
        //获取套装价格
        $goodsInfo  =  $this->getActivityGoodsInfo($actInfo,$goods_id,$is_package);
        $price = $goodsInfo['price'];
        if($discount){                                          //如果传入折扣不执行范围赠品价格
            if(is_array($discount)){                            //根据key判断条件
                if(isset($discount["reduction"])){              //减免
                    $price = $price - $discount['reduction'];
                }else{                                          //没有指定条件直接结束 抛出异常
                   throw new \Exception("没有指定折扣条件");
                }
            }else{                                  //不是数组默认打折
                $price = $price *($discount/10);
            }
                                          // 选择范围赠品价格(优先于商品直接设定价格)                  --根据gift_range 设定全部商品价格
        }elseif(!$this->checkGiftRange($actInfo ,$goods_id ,$is_package , $price)){      //将检查后的金额通过 price 直接返回
            return false;
        }
        return $price;
    }

    /**
     * 获取活动中指定商品名称 gift -> goods_name
     * @param array $actInfo
     * @param int $goods_id
     * @param bool $is_package
     * @return bool|string   false|商品名称
     */
    protected function getActivityGoodsName($actInfo , $goods_id ,$is_package = false){
        if($goods = $this->getActivityGoodsInfo($actInfo ,$goods_id , $is_package)){
            return $goods['name'];
        }
        return false;
    }

    /**
     * 获取活动指定商品信息
     * @param array $actInfo          活动信息
     * @param int $goods_id         商品id
     * @param bool $is_package  是否套装
     * @return bool|Array   Array
    (
    [id] => 1149
    [name] => 医用愈肤生物膜（水剂活性敷料）面膜(Kr.Chnskin V1.0)
    [remarks] =>
    [price] => 40
    [num] => 1
    [pmin] =>
    [pmax] =>
    [buylimit] => 5555
    )
     */
    public function getActivityGoodsInfo($actInfo , $goods_id ,$is_package = false){
        if(empty($actInfo)|| empty($goods_id)) return false;
        if($goods = $this->getActivityGoodsList($actInfo , $is_package)){
            foreach($goods as $val){
                if($goods_id == $val['id']){
                    return $val;
                }
            }
        }
        $this->setError("指定活动商品不存在");
        return false;
    }

    /**
     * 获取活动商品列表
     * @param array $actInfo          活动信息
     * @param bool $is_package  是否套装
     * @return bool|mixed
     */
    public function getActivityGoodsList($actInfo , $is_package = false){
        if(empty($actInfo)) return false;
        if($is_package){
            $goods = unserialize($actInfo['gift_package']);
        }else{
            $goods = unserialize($actInfo['gift']);
        }
        return $goods;
    }
    /*
     *  array(
     *      'buy_discount' => 购买折扣,
     * 已有   act_id   goods_id  number
     * 需要返回   price  discount act_id   goods_id      is_package                  result => array(array(每一件商品的数据,...) ) 二维数组
     * +++++
     * 1.批量删除 param(活动id列表,购物车列表) return(需要删除的购物车id rec_id) XXX
     * 解法: 使用checkCartFavourableActivity
     * 求原来$cart_act_list的rec_id 和 返回的数组key(rec_id)的差集
     * 2.批量检查 param(活动id列表,购物车列表) return 通过检查的所有数组   总价 $this->getData("total_amount");
     */

    /**
     * 批量检查购物车中活动商品是否可加入
     * @param array $cart_act_list      购物车活动商品列表
     * @param array $cart_list          购物车其他商品列表
     * @return array                    购物车可添加活动商品列表
     */
    public function checkCartFavourableActivity($cart_act_list = array(),$cart_list = array()){
        if(empty($this->favourableActivityList)){
            return array();                             //没有可用活动
        }
        $resultList = array();
        $total_amount = 0 ;
        foreach($cart_act_list as $key => $cart_act){
            $is_package = strcmp("package_buy",$cart_act['extension_code']) ? false : true;         //是否套装
            if($this->checkFavourableActivity($cart_act['is_gift'],$cart_act['goods_id'],$is_package,$cart_act['goods_number'],$cart_list)){     //可以加入购物车
                //把当前商品加入购物车再进行下一个检查
                $cart_list[] = $cart_act;
                $result = $this->getData('result');
                $arr =array();
                foreach($result as $ret){               //按价格区分 相同价格作为同一条返回
                    $total_amount += $ret['price'];
                    $price =$ret['price'];
                    if(!isset($arr[$price])){           //如果不存在这个价格的添加一条
                        $arr[$price] = $ret;
                        $arr[$price]['goods_number'] = 1;
                    }else{
                         ++ $arr[$price]['goods_number'];
                    }
                }
                $resultList[$cart_act['rec_id']] = $arr;
            }
        }
        $this->setData('total_amount',$total_amount);
        return $resultList;
    }


    /**
     * 当前商品检查结果 $this->getData('result')
     * @return null|array
     */
    public function getResult(){
        return $this->getData('result');
    }

    /**
     * 所有商品总价 $this->getData('total_amount')
     * @return null|string
     */
    public function getTotalAmount(){
        return $this->getData('total_amount');
    }

    /**
     * 获取可以加入购物车的优惠活动商品列表                                       --abraa
     * @param int $only_gift    是否只返回换购和满赠2类(0|1)
     * @return mixed            可以加入购物车的优惠活动商品列表
     */
    public function getAddToCartActivityGoodsList($only_gift = 0){
        $goodsActivityLogic =$this->getGoodsActivityLogic();
        $goodsLogic = $this->getGoodsLogic();
        //购物车商品列表
        $cartList =$this->getCartModel()->getCartList("rec_id,goods_id,goods_number,goods_name,goods_price,is_gift,extension_code",true);
        //1. 获取所有在当前时间可用的活动
        $actList = $this->getFavourableActivityList();
        //2. 获取所有可以添加到购物车的活动商品
        foreach ($actList as &$act) {
            //=====套装处理================
            $giftPackageList = unserialize($act['gift_package']);
            $giftPackageResult = $this->foreachActivityList($giftPackageList,$cartList,$act,1,$only_gift);      //结果集
            $goodsIdArr = array_keys($giftPackageResult);                                                       //活动商品id数组
            //根据活动商品判断当前商品是否可用  - 数量 - 上架 (套装和单品需要分开判断) --不可用的商品不显示
            $goodsIdArr = $goodsActivityLogic->checkGoodsActivitySaleList($goodsIdArr);         //获取可用售卖活动商品id数组 -- 返回可卖id数组
            if (!empty($goodsIdArr)) {
                $giftPackageResult = array_intersect_key($giftPackageResult,array_flip($goodsIdArr));   //取交集
            }else{
                $giftPackageResult = array();
            }
            $thumbList = $goodsActivityLogic->getGoodsActivityThumb($goodsIdArr);            //批量获取缩略图
            foreach ($thumbList as $key => $thumb) {                                        //$key   == $gift['id']
                $gift_package_result[$key]['thumb'] = $thumb;
            }
            $act['gift_package'] = $giftPackageResult;                                    //处理后结果重新赋值到活动列表
            //=========单品处理=================
            $giftList = unserialize($act['gift']);
            $giftResult = $this->foreachActivityList($giftList,$cartList,$act,0,$only_gift);      //结果集
            $goodsIdArr = array_keys($giftResult);                                                  //活动商品id数组
            //根据活动商品判断当前商品是否可用  - 数量 - 上架 (套装和单品需要分开判断) --不可用的商品不显示
            $goodsIdArr = $goodsLogic->checkGoodsSale($goodsIdArr);                      //获取可用售卖活动商品id数组
            if (!empty($goodsIdArr)) {
                $giftResult = array_intersect_key($giftResult,array_flip($goodsIdArr));   //取交集
            }else{
                $giftResult = array();
            }
            $thumbList = $goodsLogic->getGoodsThumb($goodsIdArr);            //批量获取缩略图
            foreach ($thumbList as $key => $thumb) {
                $giftResult[$key]['thumb'] = $thumb;
            }
            $act['gift'] = $giftResult;                                    //处理后结果重新赋值到活动列表
        }
        return $actList;
    }

    /**
     *  遍历检查符合优惠条件的活动商品
     * @param array $giftList         优惠活动反序列化后的商品或套装列表
     * @param array $cartList         购物车列表
     * @param array $act              当前优惠活动详情
     * @param int $isPackage        是否套装(0|1)
     * @param int $onlyGift     是否只返回换购和满赠2类(0|1)
     * @return array            可以加入购物车的活动商品列表
     */
    protected function foreachActivityList($giftList ,$cartList , &$act , $isPackage = 0 , $onlyGift = 0){
        $giftResult = array();
        foreach ($giftList as $gift) {
            // 区分换购和满赠2类(用于前端显示判断)          - is_exchange_buy 换购  - is_free_gift满赠   当前活动属于哪类根据最后一个商品来判断 ^v^ - -        -_-!

            //要有最小金额设置的是换购    + 或者有优惠范围 act_range > 0
            $act['is_exchange_buy'] = ($gift['price'] > 0  && ($gift['pmin'] > 0 || $act['min_amount'] > 0 || $act['act_range'] > 0)) ? 1 : 0 ;
            // 不要钱的是满赠
            $act['is_free_gift'] = ($gift['price'] == 0) ? 1 : 0 ;
            //是否保留不是换购和满赠的商品
            if ($onlyGift == 1 && $act['is_exchange_buy'] == 0 && $act['is_free_gift'] == 0) {     //不保留 跳过
                continue;
            }
            if ($this->checkFavourableActivity($act['act_id'], $gift['id'], $isPackage, 1, $cartList)) {        //检查该商品是否可以加入购物车
                $result = $this->getData('result');                                      //获取验证结果
                $result = current($result);
                $gift['price'] = $result['price'];                       //获取当前活动商品gift的价格
                $giftResult[$gift['id']] = $gift;
            }
        }
        return $giftResult;
    }


    /**
     * 获取可以加入购物车的指定活动活动商品列表                                       --abraa
     * @param array $act_id    指定活动id
     * @return mixed            可以加入购物车的优惠活动商品列表              [act_id=>[gift=>[],gift_package=>[]]]
     */
    public function getActivityGoodsListToActId($act_id){
        if(!is_array($act_id)){
            $act_id = explode(",",$act_id);
        }
        $goodsActivityLogic =$this->getGoodsActivityLogic();
        $goodsLogic = $this->getGoodsLogic();
        //购物车商品列表
        $cartList =$this->getCartModel()->getCartList("rec_id,goods_id,goods_number,goods_name,goods_price,is_gift,extension_code",true);
        $actList = array();
        foreach($act_id as $actId){
            //1.获取可以使用的活动
            $act = $this->getFavourableActivity($actId);
            if(empty($act)) continue;                               //不可用活动直接跳过
            //2.检查当前活动的商品是否可以加入购物车  ---满足条件并且可售卖
            //=====套装处理================
            $giftPackageList = unserialize($act['gift_package']);
            $giftPackageResult = $this->foreachActivityList($giftPackageList,$cartList,$act,1);      //结果集
            $goodsIdArr = array_keys($giftPackageResult);                                                       //活动商品id数组
            //根据活动商品判断当前商品是否可用  - 数量 - 上架 (套装和单品需要分开判断) --不可用的商品不显示
            $goodsIdArr = $goodsActivityLogic->checkGoodsActivitySaleList($goodsIdArr);         //获取可用售卖活动商品id数组 -- 返回可卖id数组
            if (!empty($goodsIdArr)) {
                $giftPackageResult = array_intersect_key($giftPackageResult,array_flip($goodsIdArr));   //取交集
            }else{
                $giftPackageResult = array();
            }
            $act['gift_package'] = $giftPackageResult;                                    //处理后结果重新赋值到活动列表
            //=========单品处理=================
            $giftList = unserialize($act['gift']);
            $giftResult = $this->foreachActivityList($giftList,$cartList,$act,0);      //结果集
            $goodsIdArr = array_keys($giftResult);                                                  //活动商品id数组
            //根据活动商品判断当前商品是否可用  - 数量 - 上架 (套装和单品需要分开判断) --不可用的商品不显示
            $goodsIdArr = $goodsLogic->checkGoodsSale($goodsIdArr);                      //获取可用售卖活动商品id数组
            if (!empty($goodsIdArr)) {
                $giftResult = array_intersect_key($giftResult,array_flip($goodsIdArr));   //取交集
            }else{
                $giftResult = array();
            }
            $act['gift'] = $giftResult;                                    //处理后结果重新赋值到活动列表
            $actList[$actId] = $act;
        }
        return $actList;
    }
}