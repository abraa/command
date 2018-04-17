<?php
/**
 * ====================================
 * 购物车相关业务处理
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-04 14:15
 * ====================================
 * File: Cart.class.php
 * ====================================
 */
namespace Common\Logic;

class Cart extends LogicData{
    /**
     * 购物车模型实例化对象
     * @var \Model|null|\Think\Model
     */
    private $CartModel = NULL;
    protected $FavourableActivityLogic = NULL;          //优惠活动逻辑处理
    protected $GoodsActivityLogic = NULL;               //套装逻辑处理
    protected $GoodsLogic = NULL;                       //商品逻辑处理

    /**
     * 会员ID，没登录则为0
     * @var int
     */
    private $userId = 0;
    public function __construct() {
        parent::__construct();
        $this->CartModel = D('Common/Home/Cart');
        $this->userId = $this->CartModel->getUser('user_id');
    }

    /**
     * 获取优惠活动处理类
     * @return FavourableActivityLogic|null
     */
    protected function getFavourableActivityLogic(){
        if(!isset($this->FavourableActivityLogic)){
            $this->FavourableActivityLogic = new FavourableActivityLogic();
        }
        return $this->FavourableActivityLogic;
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
     * 获取购物车商品列表
     */
    public function getList(){
        $pageSize = $this->getData('page_size');
        $page = $this->getData('page');
        $select = $this->getData('select');
        $isShow = intval($this->getData('is_show'));  //是否为显示到页面，如果是显示到页面，字段会不同，1=显示页面，0=仅获取数据
        $getImage = intval($this->getData('get_image'));  //是否获取缩略图图链接，1=获取，0=不获取

        //检查并且删除不可卖的商品
        $this->CartModel->checkCartGoods();

        //检查购物车活动商品是否可买
        $this->checkCartActivity();

        //获取购物车商品列表
        $data = $this->CartModel->getCartList('*', (!empty($select) ? true : false), false, -1, $page, $pageSize);

        if($data['count'] > 0){
            $selectRecId = $this->CartModel->getSelectRecId();
            $GoodsModel = D('Common/Home/Goods');
            $GoodsActivityModel = D('Common/Home/GoodsActivity');
            foreach($data['list'] as $key=>$value){
                //处理商品是否选中
                $value['select'] = false;
                if (in_array($value['rec_id'], $selectRecId)) {
                    $value['select'] = true;  //选中
                }
                //处理商品缩略图
                if($getImage > 0){
                    $object = $value['extension_code'] == 'package_buy' ? $GoodsActivityModel : $GoodsModel;
                    $value['thumb'] = $object->getThumb($value['goods_id']);
                    $value['thumb'] = !empty($value['thumb']) ? C('domain_source.img_domain') . $value['thumb'] : '';
                }
                //处理商品ID
                if(($value['extension_code'] == 'package_buy' && $value['is_gift'] <= 0) || ($value['is_gift'] > 0 && $value['goods_id'] <= 0)){
                    $value['goods_id'] = $GoodsActivityModel->getGoodsId($value['goods_id']);  //找回这个套装绑定的商品ID
                }
                //如果是显示页面的，做不同处理
                if($isShow > 0){
                    //把套装也作为单品处理
                    $value['extension_code'] = '';
                    //套装ID，把套装也作为单品处理
                    $value['pg_id'] = 0;
                }
                $data['list'][$key] = $value;
            }
        }
        return $data;
    }

    /**
     * 删除购物车选中的商品
     * @return bool
     */
    public function cleanSelect(){
        $deleteAll = intval($this->getData('delete_all'));  //是否删除所有，0=仅删除选中的，1=删除所有
        if($deleteAll > 0){  //删除全部
            $recId = $this->CartModel->getAllRecId();

        }else{  //只删除选中的
            $recId = $this->CartModel->getSelectRecId();
        }
        if(!empty($recId)){
            $this->CartModel->deleteGoods($recId);  //删除购物车商品
            $this->CartModel->getSelectRecId(true);  //删除选中的session记录
            return true;
        }
        return false;
    }

    /**
     * 根据商品ID删除对应购物车商品
     * @return bool
     */
    public function deleteGoodsId(){
        $goodsIdArray = $this->getData('goods_id');
        if(!empty($goodsIdArray)){
            $GoodsActivityModel = D('Common/Home/GoodsActivity');
            foreach($goodsIdArray as $goodsId){
                $packageInfo = $GoodsActivityModel->getInfo($goodsId, 0, 'act_id');
                $goodsId = isset($packageInfo['act_id']) ? $packageInfo['act_id'] : $goodsId;
                $recId = $this->CartModel->isInCart($goodsId);  //获取购物车ID
                if($recId > 0){
                    $this->CartModel->deleteGoods($recId);  //删除购物车商品
                }
            }
        }
        return true;
    }

    /**
     * 反选、删除购物车
     * @return bool
     */
    public function delete(){
        $realDel = $this->getData('real_del');
        $recId = $this->getData('rec_id');

        if($realDel == 1){  //硬删除
            $result = $this->CartModel->deleteGoods($recId);
        }else{  //反选
            $result = $this->CartModel->selectGoods($recId, false);
        }
        if($result === false){
            $this->setError('操作失败');
            return false;
        }
        return true;
    }

    /**
     * 全选、反选
     */
    public function selectAll(){
        $selectType = $this->getData('select_type');  //1=全选、0=全反选
        $result = $this->CartModel->selectAllGoods(($selectType==1 ? true : false));
        if($result === false){
            return false;
        }
        return true;
    }

    /**
     * 减少一个数量
     */
    public function minus(){
        $goodsId = $this->getData('goods_id');
        $actId = $this->getData('act_id');
        $actId = $actId ? $actId : 0;
        $goodsNumber = intval($this->getData('goods_number'));
        $goodsNumber = $goodsNumber>0 ? $goodsNumber : 1;

        if (empty($goodsId)) {
            $this->setError('请指定加入购物车的商品');
            return false;
        }

        //检查纠正单品或者套装
        $packageId = $this->checkGoodsId();
        $isPackage = $this->getData('is_package');

        $result = $this->getGoods(($isPackage>0 ? 0 : $goodsId), ($isPackage>0 ? ($packageId>0 ? $packageId : $goodsId) : 0), ($actId > 0 ? true : false));
        if($result === false){
            return $result;
        }
        if($actId <= 0 && empty($result['goodsInfo'])){
            $this->setError('找不到指定的商品');
            return false;
        }
        if(!empty($result['packageInfo'])){
            $isPackage = 1;
            $goodsId = $result['packageInfo']['act_id'];
        }
        if($packageId > 0){
            $goodsId = $packageId;
        }
        $goodsInfo = $result['goodsInfo'];
        $packageInfo = $result['packageInfo'];
        //开始减少商品
        $price = !empty($packageInfo) ? $packageInfo['package_price'] : (!empty($goodsInfo) ? $goodsInfo['shop_price'] : -1) ;
        $result = $this->updateGoods($goodsId, $isPackage, -$goodsNumber, '', ($actId>0 ? -1 : $price), $actId);
        if($result === false){
            return false;
        }
        return true;
    }

    /**
     * 加入购物车
     */
    public function add(){
        $goodsId = $this->getData('goods_id');
        $goodsNumber = max(intval($this->getData('goods_number')), 1);
        $actId = $this->getData('act_id');  //活动ID
        $actId = !empty($actId) ? intval($actId) : 0;
        $option = $this->getData('option');

        if (empty($goodsId)) {
            $this->setError('请指定加入购物车的商品');
            return false;
        }
        //检查纠正单品或者套装
        $packageId = $this->checkGoodsId();
        $isPackage = $this->getData('is_package');

        //获取商品信息和套装信息
        $result = $this->getGoods(($isPackage>0 ? 0 : $goodsId), ($isPackage>0 ? $packageId : 0), ($actId > 0 ? true : false));

        if($result === false){
            return $result;
        }
        $goodsInfo = $result['goodsInfo'];  //goods商品信息
        $packageInfo = $isPackage>0 ? $result['packageInfo'] : array();  //套装商品信息，非套装则为空数组

        if ($actId > 0) {           //活动
            return $this->addActivity($goodsInfo, $packageInfo);
        }else{                  //非活动
            //查询购物车是否存在该商品，存在就累加，并且选中
            $price = !empty($packageInfo) ? $packageInfo['package_price'] : (!empty($goodsInfo) ? $goodsInfo['shop_price'] : -1) ;
            $result = $this->updateGoods((!empty($packageInfo) ? $packageInfo['act_id'] : $goodsId), $isPackage, $goodsNumber, $option, $price, $actId);
            if($result === false || $result === true){
                return $result;
            }
            //加入到购物车
            $result = $this->CartModel->addToCart($goodsNumber, $goodsInfo, $packageInfo, $actId);
            if($result === false){
                $this->setError('商品添加失败');
                return false;
            }
        }
        return true;
    }

    /**
     * 检查纠正单品或者套装
     * @return int
     */
    protected function checkGoodsId(){
        $goodsId = $this->getData('goods_id');
        $isPackage = $this->getData('is_package');
        $actId = $this->getData('act_id');  //活动ID
        if($isPackage > 0){  //如果是有传标志套装，就当作是套装ID处理
            return $goodsId;
        }
        if($actId > 0){
            $isPackage = $this->checkActivityGoodsIsPackage($actId, $goodsId);
            if($isPackage > 0){  //套装
                $packageId = D('Common/Home/GoodsActivity')->getActId(0, $goodsId);
            }else{  //单品
                $packageId = D('Common/Home/GoodsActivity')->getActId($goodsId);
            }
        }else{
            //先查询出套装ID，除非不是套装
            $packageId = D('Common/Home/GoodsActivity')->getActId($goodsId);
        }
        $packageId = $packageId ? $packageId : 0;
        $this->setData('package_id', 0);
        $this->setData('is_package', 0);
        if($packageId > 0){
            $this->setData('package_id', $packageId);
            $this->setData('is_package', 1);
        }
        return $packageId;
    }

    /**
     * 检查活动商品是否为套装
     * @param $actId
     * @param $goodsId
     * @return int
     */
    protected function checkActivityGoodsIsPackage($actId, $goodsId){
        $activity = D('Common/Home/FavourableActivity')->getActivity($actId);
        if(!empty($activity)){
            $giftPackage = !empty($activity['gift_package']) ? unserialize($activity['gift_package']) : array();
            if(!empty($giftPackage)){
                foreach($giftPackage as $value){
                    if($value['id'] == $goodsId){
                        return 1;
                    }
                }
            }
        }
        return 0;
    }

    /**
     * 处理活动商品到购物车
     * @param array $goodsInfo  商品详情
     * @param array $packageInfo 套装详情
     * @return bool
     */
    protected function addActivity($goodsInfo, $packageInfo){
        if(empty($goodsInfo) && empty($packageInfo)){
            $this->setError('商品不存在或已下架');
            return false;
        }
        $goodsId = $this->getData('goods_id');
        $isPackage = $this->getData('is_package');
        $goodsNumber = max(intval($this->getData('goods_number')), 1);
        $actId = $this->getData('act_id');  //活动ID
        $actId = !empty($actId) ? intval($actId) : 0;
        $option = $this->getData('option');

        $field = 'rec_id,user_id,goods_id,goods_sn,goods_name,goods_number,goods_price,market_price,is_gift,parent_id,extension_code';
        $cartList = $this->CartModel->getCartList($field,true);                      //获取购物车已有商品列表

        if($this->getFavourableActivityLogic()->checkFavourableActivity($actId,$goodsId,$isPackage,$goodsNumber,$cartList)){
            $result = $this->getFavourableActivityLogic()->getResult();      //获取检查结果

            if($result === false){
                $this->setError('商品添加失败');
                return false;
            }
            $this->CartModel->startTrans();  //开启事务
            foreach($result as $key =>$val){
                if($isPackage){
                    $packageInfo['goods_price'] = $val['price'];
                    $packageInfo['package_price'] = $val['price'];
                }else{
                    $goodsInfo['shop_price'] = $val['price'];
                    $goodsInfo['goods_price'] = $val['price'];
                }

                //查询购物车是否存在该商品，存在就累加，并且选中
                $result = $this->updateGoods($goodsId, $isPackage, $goodsNumber, $option, $val['price'], $actId);

                if($result === false){
                    $this->CartModel->rollback();  //回滚
                    return $result;
                }else if($result === true){
                    continue;
                }
                //加入到购物车
                $result = $this->CartModel->addToCart($goodsNumber, $goodsInfo, $packageInfo, $actId, 1, false);
                if($result === false){
                    $this->CartModel->rollback();  //回滚
                    $this->setError('商品添加失败');
                    return false;
                }
            }
            $this->CartModel->commit();  //保存事务数据
        }else{                                      //活动检查失败获取错误
            $error = $this->getFavourableActivityLogic()->getError();
            $this->setError($error);
            return false;
        }
        return true;
    }

    /**
     * 检查购物车活动商品
     */
    public function checkCartActivity(){
        $cartGoods = $this->explodeCartGoodsActivity();
        $result = $this->getFavourableActivityLogic()->checkCartFavourableActivity($cartGoods['cartActList'], $cartGoods['cartPlainList']);

        //有购物车数据，处理掉不可买的商品
        if(!empty($result)){
            foreach($result as $recId=>$goodsList){
                //处理多个价格的情况，多价格出现多条商品数据
                if(!empty($goodsList)){
                    $i = 0;
                    foreach($goodsList as $price=>$goods){
                        $addCartGoods = $cartGoods['cartList'][$recId];
                        $i++;
                        if($i == 1){
                            $addCartGoods['goods_attr_id'] = $this->setCartGoodsAttr($addCartGoods['goods_attr_id'], 'shipping_free', $goods['shipping_free']);
                            $this->CartModel->where(array('rec_id'=>$recId))->save(array(
                                'goods_number'=>$goods['goods_number'],
                                'goods_price'=>$goods['price'],
                                'goods_attr_id'=>$addCartGoods['goods_attr_id'],
                            ));
                        }else{
                            //加入到购物车
                            $addCartGoods['goods_attr_id'] = $this->setCartGoodsAttr($addCartGoods['goods_attr_id'], 'shipping_free', $goods['shipping_free']);
                            $addCartGoods['goods_price'] = $goods['price'];
                            $addCartGoods['goods_number'] = $goods['goods_number'];
                            $addCartGoods['addtime'] = \Common\Extend\Time::gmTime();
                            unset($addCartGoods['rec_id']);
                            $newRecId = $this->CartModel->add($addCartGoods);  //插入商品
                            $this->CartModel->selectGoods($newRecId);  //选中
                            //如果是套装，插入套装子商品
                            if(isset($addCartGoods['package_goods']) && !empty($addCartGoods['package_goods'])){
                                $packageGoods = $addCartGoods['package_goods'];
                                $addCartGoods = array();
                                foreach($packageGoods as $value){
                                    unset($value['rec_id']);
                                    $value['parent_id'] = $newRecId;
                                    $value['addtime'] = \Common\Extend\Time::gmTime();
                                    $addCartGoods[] = $value;
                                }
                                $this->CartModel->addAll($addCartGoods);
                            }
                        }
                    }
                }
            }
            //删除不可买的商品
            $onRecId = !empty($result) ? array_keys($result) : array();
            $allRecId = !empty($cartGoods['cartActList']) ? array_keys($cartGoods['cartActList']) : array();
            if(!empty($allRecId) && !empty($allRecId)){
                $offRecId = array_diff($allRecId, $onRecId);  //差集，不可买的商品
                if(!empty($offRecId)){
                    $this->CartModel->deleteGoods($offRecId);
                }
            }
        }else if(!empty($cartGoods['cartList'])){  //没选中的购物车数据，删除已选中的所有商品
            $recIds = !empty($cartGoods['cartActList']) ? array_keys($cartGoods['cartActList']) : array();
            if(!empty($recIds)){
                $this->CartModel->deleteGoods($recIds);
            }
        }
    }

    /**
     * 设置购物车商品的goods_attr_id字段数据
     * @param string $goodsAttrId
     * @param $key
     * @param $value
     * @return string
     */
    protected function setCartGoodsAttr($goodsAttrId = '', $key, $value){
        $goodsAttrId = !empty($goodsAttrId) ? unserialize($goodsAttrId) : array();
        $goodsAttrId[$key] = $value;
        return serialize($goodsAttrId);
    }

    /**
     * 分离购物车商品
     * @return array
     */
    protected function explodeCartGoodsActivity(){
        $cartList = $this->CartModel->getCartList('*', true, true);                      //获取购物车已有商品列表
        $cartData = array();
        $cartActList = array();  //活动商品
        $cartPlainList = array();  //普通商品
        if(!empty($cartList)){
            foreach($cartList as $value){
                if($value['extension_code'] != 'package_goods'){
                    if($value['is_gift'] > 0){
                        $cartActList[$value['rec_id']] = $value;
                    }else{
                        $cartPlainList[$value['rec_id']] = $value;
                    }
                    $cartData[$value['rec_id']] = $value;
                }else{
                    if(isset($cartData[$value['parent_id']])){
                        $cartData[$value['parent_id']]['package_goods'] = $value;
                    }
                }
            }
        }
        return array(
            'cartList'=>$cartData,
            'cartActList'=>$cartActList,
            'cartPlainList'=>$cartPlainList,
        );
    }

    /**
     * 更新购物车数量、选中
     * @param int $goodsId 商品ID
     * @param int $isPackage 是否为套装
     * @param int $goodsNumber 增加或者减少的商品数量
     * @param string $option 是否为选中操作
     * @param int $goodsPrice  是否校验商品价格  -1 不校验
     * @param int $activityId 活动ID
     * @return bool
     */
    private function updateGoods($goodsId = 0, $isPackage = 0, $goodsNumber = 0, $option = '',$goodsPrice = -1, $activityId = 0){
        $recId = $this->CartModel->isInCart($goodsId, $isPackage, -1 , $goodsPrice, $activityId);
        if($recId > 0){
            if ($option == 'select') {  //选中商品
                $this->CartModel->selectGoods($recId);  //选中
                return true;
            } else {  //更新数量,并且选中
                $result = $this->CartModel->setGoodsNumber($recId, $goodsNumber);  //累加数量
                if($result === false){
                    $this->setError('商品更新失败');
                    return false;
                }else if(is_null($result)){  //数量不能少于1
                    $this->setError('商品数量不能少于1');
                    return false;
                }
                //选中
                $this->CartModel->selectGoods($recId);
                return true;
            }
        }
    }

    /**
     * 获取商品信息，以及套装信息
     * @param int $goodsId 商品ID
     * @param int $actId  套装ID
     * @param bool $isActivity  是否活动 true or false
     * @return bool
     */
    private function getGoods($goodsId, $actId = 0, $isActivity = false){
        //如果是套装，先取出商品ID
        if($goodsId <= 0 && $actId > 0){
            $packageInfo = $this->getPackage($actId, $isActivity);
            if(empty($packageInfo)){
                return false;
            }
            $goodsId = $packageInfo['goods_id'];
        }
        $goodsInfo = $this->CartModel->getProductInfo($goodsId);  //查询商品
        if($isActivity === false && empty($goodsInfo)){  //不是活动并且不存在此商品
            $this->setError('商品缺货下架，抓紧咨询客服抢购吧！');
            return false;
        }
        //查询套装商品
        if(!isset($packageInfo) && $actId > 0){
            $packageInfo = $this->CartModel->getProductInfo(($goodsId > 0 ? $goodsId : 0), 1, ($actId > 0 ? $actId : $goodsId));  //获取套装
            if(!empty($packageInfo)){
                $packageInfo['market_price'] = $goodsInfo['market_price'];
                if($packageInfo['is_on_sale'] == 0){  //没开始套装或已结束
                    $this->setError('商品缺货下架，抓紧咨询客服抢购吧！');
                    return false;
                }
            }
        }else if(!empty($packageInfo)){
            $packageInfo['market_price'] = $goodsInfo['market_price'];
        }

        //检查商品是否可售卖
        if($isActivity === false){
            $result = $this->checkGoods($goodsInfo);
            if($result === false){
                return false;
            }
        }
        return array(
            'goodsInfo'=>empty($goodsInfo) ? array() : $goodsInfo,
            'packageInfo'=>empty($packageInfo) ? array() : $packageInfo
        );
    }

    /**
     * 获取套装详情
     * @param int $actId 套装ID
     * @param bool $isActivity 是否活动商品
     * @return array|bool
     */
    protected function getPackage($actId, $isActivity = false){
        $packageInfo = $this->CartModel->getProductInfo(0, 1, $actId);  //获取套装
        if(!empty($packageInfo)){
            if($isActivity === false && $packageInfo['is_on_sale'] == 0){  //没开始套装或已结束
                $this->setError('商品缺货下架，抓紧咨询客服抢购吧！');
                return false;
            }
        }else{
            $this->setError('商品缺货下架，抓紧咨询客服抢购吧！');
            return false;
        }
        return $packageInfo;
    }

    /**
     * 检查商品是否可售卖
     * @param $goodsInfo
     * @return bool
     */
    protected function checkGoods($goodsInfo){
        //校验是否药品商品、是否可售卖
        $result = D('Common/Home/Goods')->checkDrugSale($goodsInfo['goods_id']);
        if($result === false){
            $this->setError('下单请咨询诊疗顾问，我们会根据您的皮肤情况确定药品的用量更安全，更有效！点击咨询');
            return false;
        }
        if($goodsInfo['is_on_sale'] == 0 || $goodsInfo['is_alone_sale'] == 0 || (CHECK_STOCK && $goodsInfo['goods_number'] <= 0)){
            $this->setError('商品缺货下架，抓紧咨询客服抢购吧！');
            return false;
        }
        return true;
    }
}

