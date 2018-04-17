<?php
/**
 *  * 优惠券相关操作 类
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/9/22
 * Time: 17:26
 */
namespace Common\Logic;
use Common\Extend\Time;
use Common\Extend\Order\Order;
use Common\Extend\Order\Favourable;

class BonusLogic extends LogicData
{
    private $sessionId = NULL;                  //session ID
    private $user_id = NULL;                       //当前登录的用户ID

    protected $BonusTypeModel;                      //优惠券类型模型
    protected $UserBonusModel;                      //用户优惠券模型
    protected $CartModel;                           //购物车模型

    protected $userBonusList;                         //用户优惠券列表
    protected $userBonusTypeList;                    //用户可使用优惠券类型列表
    protected $cartList;                            //购物车已选中商品列表


    public function __construct($cartList = array())
    {
        parent::__construct();
        $this->sessionId = session_id();  //获取当前session ID
        if(!empty($cartList)){
            $this->cartList = $cartList;
        }
    }

    /**
     * 获取优惠券类型模型
     * @return \BonusTypeModel|\Think\Model
     */
    protected function getBonusTypeModel()
    {
        if (!isset($this->BonusTypeModel)) {
            $this->BonusTypeModel = D('Common/Home/BonusType');
        }
        return $this->BonusTypeModel;
    }

    /**
     * 获取用户优惠券模型
     * @return \UserBonusCenterModel|\Think\Model
     */
    protected function getUserBonusModel()
    {
        if (!isset($this->UserBonusModel)) {
            $this->UserBonusModel = D('Common/Home/UserBonus');
        }
        return $this->UserBonusModel;
    }

    /**
     * 获取购物车模型
     * @return \CartModel|\Think\Model
     */
    protected function getCartModel()
    {
        if (!isset($this->CartModel)) {
            $this->CartModel = D('Common/Home/Cart');
        }
        return $this->CartModel;
    }

    /**
     * 获取当前用户id
     * @return int
     */
    protected function getUserId()
    {
        if (!isset($this->user_id)) {
           $this->user_id = $this->getBonusTypeModel()->getUser("user_id");
        }
        return $this->user_id;
    }

    /**
     * 获取购物车选中商品列表
     * @return array
     */
    protected function getCartList()
    {
        if (!isset($this->cartList)) {
            $this->cartList = $this->getCartModel()->getCartList("rec_id,goods_id,goods_number,goods_name,goods_price,market_price,is_gift,extension_code",true);
        }
        return empty($this->cartList) ? array() : $this->cartList;
    }

    /**
     * 获取用户可使用优惠券列表
     * @return mixed
     */
    public function getUserBonusList(){
        if(!isset($this->userBonusList)){
            $this->userBonusList = $this->getUserBonusModel()->getUserBonus($this->getUserId());
        }
       return empty($this->userBonusList) ? array() : $this->userBonusList;
    }

    /**
     * 获取用户指定type优惠券信息
     * @param $typeId
     * @return mixed
     */
    public function getUserBonus($typeId){
        if(!isset($this->userBonusList[$typeId])){
            $this->getUserBonusList();
        }
        return $this->userBonusList[$typeId];
    }

    /**
     * 获取用户可使用优惠券类型列表
     * @param array $typeIds
     * @param bool $autoBonus          --是否包含线下发放的可重复使用优惠券
     * @return array
     */
    public function getUserBonusTypeList($typeIds = array(),$autoBonus = false){
        if(empty($typeIds)){
            $typeIds = array_keys($this->getUserBonusList());
        }
        if(!isset($this->userBonusTypeList)){
            $this->userBonusTypeList = $this->getBonusTypeModel()->getUserBonusType($typeIds);
        }
        if($autoBonus){
            $typeList = $this->getBonusTypeModel()->getOfflineBonusList();                  //获取线下发放的红包
            if(is_array($typeList)){
                $this->userBonusTypeList = array_merge($this->userBonusTypeList,$typeList);
            }
        }
        return empty($this->userBonusTypeList) ? array() : $this->userBonusTypeList;
    }

    /**
     *  获取指定type优惠券类型
     * @param int $typeId    bonus  type_id
     * @return bool|
     */
    public function getBonusType($typeId){
        if(empty($typeId)){
            return false;
        }
        if(!isset($this->userBonusTypeList[$typeId])){
            $result = $this->getBonusTypeModel()->getUserBonusType($typeId);
            return $result[$typeId];
        }
        return $this->userBonusTypeList[$typeId];
    }


    /**
     * 取得购物车内能使用的用户优惠券
     * @param bool $autoBonus       是否获取可重复使用线下优惠券
     * @return bool
     */
    public function getAvailableBonus($autoBonus = false){
        $useBonus = D('Common/Home/ShopConfig')->config('use_bonus');  //获取是否开启了优惠券
        if ($useBonus == '1'){
            $bonusList = $this->getUserBonusTypeList(array(),$autoBonus);  //获取可用的优惠券类型

            $cartList = $this->getCartList();                               //获取购物车选中商品列表
            $cartTotal = $this->getCartModel()->totalCart($cartList,true);
            foreach($bonusList as $key => $v){
                if(!$this->checkBonus($v['type_id'],$cartTotal)){                             //没有通过检查的unset
                    unset($bonusList[$key]);
                    continue;
                }
                $coupon = $this->getCoupon();                            //获取检查后的添加的额外优惠券信息
                if(!empty($coupon)){                                    //如果有则合并到优惠券列表
                    $v = $coupon;
                }
                if(!isset($v['count'])){                                //绑定优惠券数量
                    $v['count'] = $this->userBonusList[$v['type_id']]['count'] ? (int)$this->userBonusList[$v['type_id']]['count'] : 1;
                }
                $bonusList[$key] = $this->formatTypeMoney($v);          //格式化金额 并加上 format_type_money
            }
            return $bonusList;
        }
        return false;
    }




    /**
     *    判断优惠券是否可用于当前购物车
     * @param int $bonusTypeId 优惠券类型id
     * @param array $cartTotal 购物车统计列表         array("goods_price"=>100, 'market_price'=>2000,'have_package'=>1,'have_gift'=>1,'goods_number'=>10,...)
     * @return array 当前优惠券是否通过检查
     */
    public function checkBonus($bonusTypeId , $cartTotal = array()){
        $bonus = $this->getBonusType($bonusTypeId);         //获取优惠券类型信息
        $userBonus = $this->getUserBonus($bonusTypeId);     //获取用户优惠券信息

        return $this->checkAll($bonus,$cartTotal,$userBonus);           //开始检查(全部)
    }


    /**
     *    判断优惠券是否可用于当前购物车             --指定使用某一张优惠券
     * @param int $userBonusId 指定优惠券id
     * @param array $cartTotal 购物车统计列表         array("goods_price"=>100, 'market_price'=>2000,'have_package'=>1,'have_gift'=>1,'goods_number'=>10,...)
     * @return array 当前优惠券是否通过检查
     */
    public function checkBonusToBonusId($userBonusId , $cartTotal = array()){
        $userBonus = $this->getUserBonusModel()->getInfo($userBonusId);     //获取用户优惠券信息
        $bonus = $this->getBonusTypeModel()->getInfo($userBonus['bonus_type_id']);         //获取优惠券类型信息

       return $this->checkAll($bonus,$cartTotal,$userBonus);        //开始检查(全部)
    }

    /**
     *    判断优惠券是否可用于当前购物车             --指定使用某一张优惠券    --通过编号
     * @param int $userBonusSn 指定优惠券编号
     * @param array $cartTotal 购物车统计列表         array("goods_price"=>100, 'market_price'=>2000,'have_package'=>1,'have_gift'=>1,'goods_number'=>10,...)
     * @return array 当前优惠券是否通过检查
     */
    public function checkBonusToBonusSn($userBonusSn , $cartTotal = array()){
        $userBonus = $this->getUserBonusModel()->getInfo('',$userBonusSn);     //获取用户优惠券信息
        $bonus = $this->getBonusTypeModel()->getInfo($userBonus['bonus_type_id']);         //获取优惠券类型信息

        return $this->checkAll($bonus,$cartTotal,$userBonus);        //开始检查(全部)
    }

    /**
     * 全部检查
     * @param $bonus        优惠券类型信息
     * @param $cartTotal
     * @param $userBonus    用户优惠券信息
     * @return bool
     */
    protected function checkAll($bonus,$cartTotal,$userBonus){
        if(empty($cartTotal)){                              //如果没有传统计信息则获取          --如果是需要检查多次的话最好是传进来节省时间多次统计相同的东西
            $cartTotal = $this->getCartModel()->totalCart($this->getCartList(),true);
        }
        //检查其他通用条件                    --非表字段检查
        if(!$this->checkCommon($bonus,$cartTotal,$userBonus)){
            return false;
        }
        //1.最小订单金额
        if(!$this->checkMinGoodsAmount($bonus,$cartTotal,$userBonus)){
            return false;
        }
        // 2. 使用日期
        if(!$this->checkUseDate($bonus,$cartTotal,$userBonus)){
            return  false;
        }
        //3. 是否限制套装
        if(!$this->checkIsPackage($bonus,$cartTotal,$userBonus)){
            return  false;
        }
        //4. 优惠劵和其他优惠同时使用  在线支付 - 会员优惠 - 优惠活动
        if(!$this->checkOtherGift($bonus,$cartTotal,$userBonus)){
            return  false;
        }
        //5. 使用站点
        if(!$this->checkSite($bonus,$cartTotal,$userBonus)){
            return false;
        }
        //6. 优惠范围
        if(!$this->checkCouponRange($bonus,$cartTotal,$userBonus)){
            return false;
        }

        //7. 优惠类型处理
        $ret = array();
        switch($bonus['coupon_type']){
            case 0:                                                         //普通类型
                $ret['type_money'] = $bonus['type_money'];
                break;
            case 1:                                                         //免邮券
                if( floatval($cartTotal['goods_price']) > 200 ){            //验证免邮券
                    $this->setError("抱歉，你的购物金额大于200元，系统已经自动帮你免邮，无须使用此优惠劵");
                    return false;
                }
                $ret['free_postage'] = 1;
                break;
            case 2:                                                         //实物劵
                if(!empty($bonus['type_info'])){
                    $act_id_arr = explode(',',$bonus['type_info']);
                    $front_actid = array();
                    foreach ($act_id_arr as $aid){
                        $tmp = explode('|',$aid);
                        $front_actid[] = $tmp[0];   //前台活动id
                        $back_actid[] = $tmp[1];  //业务后台活动id
                    }
                    $ret['front_actid'] =  $front_actid;
                    $ret['back_actid'] = $back_actid;
                }
                break;
            case 3:                                                         //折扣劵
                if(intval($bonus['type_info']) <> 0){
                    $ret['discount'] = floatval(intval($bonus['type_info'])/100);
                }
                break;
            default:
                return false;
        }
        if(empty($bonus['bonus_id'])) $bonus['bonus_id'] = $userBonus['bonus_id'] ;         //把当前检查用户优惠券的id也作为优惠券信息的一部分返回
        $this->setData("coupon",array_merge($ret,(array)$bonus));                          //设置额外的优惠券信息  , --  需要在调用后面处理 , 不需要当我没说
        return true;
    }

    protected function checkCommon($bonus  ,$cartTotal ,$userBonus = array()){
        if(!is_array($bonus) || empty($bonus)){
            $this->setError("抱歉，优惠劵不存在");
            return false;
        }
        //判断优惠券为可用优惠劵
        if(!(($bonus['reuse'] == 0 && $bonus['type_money'] >= 0 && empty($bonus['order_id']) && empty($bonus['site_id']))
            || ($bonus['reuse'] == 1 && $bonus['type_money'] >= 0))){               //判断优惠券为可用优惠劵
            $this->setError("抱歉，无效的优惠劵");
            return false;
        }
        //判断优惠券是否属于当前用户
        if(!empty($userBonus) && $bonus['reuse'] == 0 && $this->getUserId() != $userBonus['user_id']){  //验证user_id
            $this->setError("抱歉，你不能使用此优惠劵");
            return false;
        }
        //验证购物车是否为空
        if (!isset($cartTotal['goods_number']) || 0 >= $cartTotal['goods_number']){
            $this->setError("抱歉，你的购物车为空，不能使用此优惠劵");
            return false;
        }
        //支付金额少于优惠券面值金额
        if(!isset($cartTotal['goods_price']) || floatval($cartTotal['goods_price']) <= $bonus['type_money']){
            $this->setError("抱歉，购物金额少于优惠劵金额，不能使用此优惠劵");
            return false;
        }
        return true;
    }

    /**
     * 检查优惠券最小订单金额
     * @param $bonus
     * @param $cartTotal
     * @param array $userBonus
     * @return bool
     */
    protected function checkMinGoodsAmount($bonus,$cartTotal,$userBonus = array()){
        if(!isset($bonus['min_goods_amount'])) return false;
        if ($bonus['min_goods_amount'] > floatval($cartTotal['goods_price'])){                  //订单金额不满足优惠券最小订单金额
            $this->setError("抱歉，消费金额不足".$bonus['min_goods_amount']."元，不能使用此优惠劵");
            return false;
        }
        return true;
    }
    /**
     * 检查优惠使用期限
     * @param $bonus
     * @param $cartTotal
     * @param array $userBonus
     * @return bool
     */
    protected function checkUseDate($bonus,$cartTotal,$userBonus = array()){
        $now = Time::gmTime();
        if ($now > $bonus['use_end_date'] ||  $now < $bonus['use_start_date']){
            $this->setError("抱歉，".$bonus['type_name'].'优惠劵使用期无效,使用时间为：'.Time::localDate('Y-m-d',$bonus['use_start_date']).' 至 '.Time::localDate('Y-m-d',$bonus['use_end_date']));
            return false;

        }elseif(!empty($userBonus['start_time']) && !empty($userBonus['end_time'])  && ($now < $userBonus['start_time'] || $now > $userBonus['end_time'])) {  //验证生日优惠券是否过期
            $this->setError("抱歉，" . $bonus['type_name'] . '优惠劵使用期无效,使用时间为：' . Time::localDate('Y-m-d', $userBonus['start_time']) . ' 至 ' . Time::localDate('Y-m-d', $userBonus['end_time']));
            return false;
        }
        return true;
    }


    /**
     * 检查是否限定套餐可用
     * @param $bonus
     * @param $cartTotal
     * @param array $userBonus
     * @return bool
     */
    protected function checkIsPackage($bonus,$cartTotal,$userBonus = array()){
        if(!isset($bonus['is_package'])) return false;
        if( $bonus['is_package'] == 1 && intval($cartTotal['have_package']) <> 1 ){  //限制套装, 但购物车没有套装
            $this->setError("抱歉，只有购买套装才可以使用此优惠劵");
            return false;
        }
        return true;
    }

    /**
     * @param $bonus
     * @param $cartTotal
     * @param array $userBonus
     * @return bool
     */
    protected function checkOtherGift($bonus,$cartTotal,$userBonus = array()){
        if(!isset($bonus['is_other_gift']) || !isset($bonus['is_other_gift']) || !isset($bonus['is_other_gift'])) return false;
        if($cartTotal['have_gift'] && $bonus['is_other_gift'] <> 1 && $bonus['coupon_range'] <> 4){                //购物车有活动商品 ,优惠券未勾选其他优惠品同时使用 并且当前优惠范围不是指定活动
            $this->setError("抱歉，此优惠劵不能和其他优惠品同时使用");
            return false;
        }
        if($bonus['is_member_discount'] <> 1 && 0 < $this->getUserId()){                      //未勾选会员优惠已登录则不可用
            $this->setError("抱歉，此优惠劵不能和会员优惠同时使用");              //注: 这项的意思是这个优惠券能否和会员优惠同时使用,也就是说没有会员优惠(未登录)的时候你一定是可以使用的(不管有没有勾选),不能用的情况只有这一个
            return false;
        }
        $payment_id = session('payment_data.payment_id');                           //支付方式 1货到付款(如果选择货到付款则不属于在线支付)
        if(1 <> $payment_id && ONLINE_PAYMENT_DISCOUNT == 1 && $bonus['is_payonline_discount']!=1){  //开启了在线支付优惠,优惠券没有勾选在线支付优惠则不能用
            $this->setError("抱歉，此优惠劵不能和在线支付优惠同时使用");
            return false;
        }
        return true;
    }

    /**
     * 检查是否当前站点可用
     * @param $bonus
     * @param $cartTotal
     * @param array $userBonus
     * @return bool
     */
    protected function checkSite($bonus,$cartTotal,$userBonus = array()){
        $permit_site = explode(",",$bonus['use_site']);
        $site_id=C('SITE_ID');
        if(!empty($bonus['use_site']) && !in_array($site_id,$permit_site)){   //验证站点id
            $this->setError("抱歉，当前站点不能使用此优惠劵");
            return false;
        }
        return true;
    }

    /**
     * 检查优惠范围
     * @param $bonus
     * @param $cartTotal
     * @param array $userBonus
     * @return bool
     */
    protected function checkCouponRange($bonus,$cartTotal,$userBonus = array()){
        $couponRangeInfo = explode(",",$bonus['coupon_range_info']);                          //范围id
        switch($bonus['coupon_range']){
            case 0:                                                     //全部商品
                return true;
                break;
            case 1:                                                     //指定分类
                $intersect = array_intersect($couponRangeInfo,$cartTotal['cat_ids']);       //取优惠范围id和购物车分类ID交集
                if(empty($intersect)){
                    $this->setError("抱歉，只有购买指定分类下的商品才可以使用此优惠劵");
                    return false;
                }
                break;
            case 2:                                                     //指定套装
                $intersect = array_intersect($couponRangeInfo,$cartTotal['package_ids']);       //取优惠范围id和购物车套装ID交集
                if(empty($intersect)){
                    $this->setError("抱歉，只有购买指定套装才可以使用此优惠劵");
                    return false;
                }
                if(0 < $bonus['amount_range_limit']){  //如果指定了优惠范围价格限制
                    $cartList = $this->getCartList();
                    $amount = 0;                            //指定ID购物车总金额
                    foreach($cartList as $val){
                        if(0 == strcmp("package_buy",$val['extension_code']) && in_array($val['goods_id'],$intersect)){     //是套装并符合指定套装范围
                            $amount +=$val['goods_price'] * $val['goods_number'];
                        }
                    }
                    if($amount < $bonus['amount_range_limit']){
                        $this->setError("抱歉，优惠范围内的商品总价没有达到金额下限！");
                        return false;
                    }
                }
                break;
            case 3:                                                         //指定单品
                $intersect = array_intersect($couponRangeInfo,$cartTotal['goods_ids']);       //取优惠范围id和购物车单品ID交集
                if(empty($intersect)){
                    $this->setError("抱歉，只有购买指定单品才可以使用此优惠劵");
                    return false;
                }
                if(0 < $bonus['amount_range_limit']){  //如果指定了优惠范围价格限制
                    $cartList = $this->getCartList();
                    $amount = 0;                            //指定ID购物车总金额
                    foreach($cartList as $val){
                        if(0 <> strcmp("package_buy",$val['extension_code']) && in_array($val['goods_id'],$intersect)){     //是单品并符合指定单品范围
                            $amount +=$val['goods_price'] * $val['goods_number'];
                        }
                    }
                    if($amount < $bonus['amount_range_limit']){
                        $this->setError("抱歉，优惠范围内的商品总价没有达到金额下限！");
                        return false;
                    }
                }
                break;
            case 4:                                                         //指定活动
                $intersect = array_intersect($couponRangeInfo,$cartTotal['gift_ids']);       //取优惠范围id和购物车活动ID交集
                if(empty($intersect)){
                    $this->setError("抱歉，只有购买指定活动下的商品才可以使用此优惠劵");
                    return false;
                }
                if(0 < $bonus['amount_range_limit']){  //如果指定了优惠范围价格限制
                    $cartList = $this->getCartList();
                    $amount = 0;                            //指定ID购物车总金额
                    foreach($cartList as $val){
                        if(0 < $val['is_gift']  && in_array($val['is_gift'],$intersect)){     //是活动并符合指定活动范围
                            $amount +=$val['goods_price'] * $val['goods_number'];
                        }
                    }
                    if($amount < $bonus['amount_range_limit']){
                        $this->setError("抱歉，优惠范围内的商品总价没有达到金额下限！");
                        return false;
                    }
                }
                break;
            case 5:                                                         //指定套装和商品
                $gids = $cartTotal['goods_ids'];  //单品id
                $pids = $cartTotal['package_ids'];  //套装id
                foreach($pids as $k => $pid){
                    $pids[$k] = 'p'.$pid;
                }
                $merge_ids = array_merge($gids,$pids);   //合并
                $intersect = array_intersect($couponRangeInfo,$merge_ids);       //取优惠范围id和购物车活动ID交集
                if(empty($intersect)){
                    $this->setError("抱歉，只有购买指定单品或者套装才可以使用此优惠劵");
                    return false;
                }
                if(0 < $bonus['amount_range_limit']){  //如果指定了优惠范围价格限制
                    $cartList = $this->getCartList();
                    $amount = 0;                            //指定ID购物车总金额
                    foreach($cartList as $val){
                        $goods_id = $val['goods_id'];
                        if(0 == strcmp("package_buy",$val['extension_code'])){      //是套装把goods_id 改成套装id
                            $goods_id = "p".$goods_id;
                        }
                        if(in_array($goods_id,$intersect)){                         //符合指定单品或套装范围
                            $amount +=$val['goods_price'] * $val['goods_number'];
                        }
                    }
                    if($amount < $bonus['amount_range_limit']){
                        $this->setError("抱歉，优惠范围内的商品总价没有达到金额下限！");
                        return false;
                    }
                }
                break;
            default:
                return false;
        }
        return true;
    }

    /**
    *	优惠券类型名称
    *	@Author 9009123 (Lemonice)
    *	@param array $ub  优惠券详情
    *	@return array
    */
    private function formatTypeMoney($ub = array()){
        if(empty($ub)){
            return $ub;
        }
        $ub['format_type_money'] = priceFormat($ub['type_money']);
        if($ub['coupon_type'] == 1){
            $ub['format_type_money'] = "免邮优惠";
        }elseif($ub['coupon_type'] == 2){
            $ub['format_type_money'] = "免费赠品优惠";
        }elseif($ub['coupon_type'] == 3){
            $ub['format_type_money'] = floatval($ub['type_info'] / 10)."折优惠";
        }
        return $ub;
    }

    /**
     * 获取checkBonus后额外优惠券信息
     * @return null|array
     */
    public function getCoupon(){
        return $this->getData("coupon");
    }


    /**
     * 使用优惠劵
     * @param int $bonus_id
     * @param int $order_id
     * @param string $site_id
     * @param int $user_id
     * @return bool|string
     */
    public function useBonus($bonus_id = 0, $order_id = 0, $site_id = '', $user_id = 0){
        if($bonus_id == 0 || $order_id == 0 || $site_id == ''){
            return false;
        }
        if($this->checkBonusToBonusId($bonus_id)){                   //通过检查可以使用
            $data = array(
                'order_id'=>$order_id,
                'site_id'=>$site_id,
                'user_id'=>$user_id,
                'used_time'=>time(),
            );
            $bonus = $this->getCoupon();                            //当前优惠券处理后信息  bonusType  + ret(额外添加信息)
            if($bonus['reuse']==1){
                $use_amount = $this->getUserBonusModel()->where("bonus_id = '$bonus_id'")->getField('use_amount');
                $data['use_amount'] = $use_amount+1;
            }
            return $this->getUserBonusModel()->where("bonus_id = '$bonus_id'")->update($data);
        }
        return false;

    }
}
?>