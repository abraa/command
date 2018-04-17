<?php
/**
 * ====================================
 * 微信公众号 - 文字菜单
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 16:27
 * ====================================
 * File: Menu.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;
use Think\Cache\Driver\Memcache;
use Common\Logic\Wechat\WechatData;

class Menu extends WechatData{
    /**
     * memcache对象
     * @var null
     */
    private $Memcache = NULL;
    /**
     * 菜单可执行函数的函数名前缀
     * @var string
     */
    private $menuFix = '__';
    /**
     * 用户手机号码
     * @var null
     */
    private $mobile = NULL;

    /**
     * 定义客服文本菜单
     * @var array
     */
    private $menu_array = array(
        array(
            'number' => 1,  //回复序号
            'title' => '查询订单与快递信息',  //显示的名称标题
            'function' => 'sendMemberLogistics',  //处理的方法
        ),
        array(
            'number' => 2,  //回复序号
            'title' => '查询会员等级',  //显示的名称标题
            'function' => 'sendMemberLevel',  //处理的方法
        ),
        array(
            'number' => 3,  //回复序号
            'title' => '查询会员积分',  //显示的名称标题
            'function' => 'sendMemberIntegral',  //处理的方法
        ),
    );

    /**
     * 发送物流信息
     */
    public function show(){
        $data = $this->getDatas();
        $menuText = $this->createMenuText();
        if (!empty($menuText)) {
            $text = $menuText . "\n\n" . '请回复上面序号继续操作。';
            $this->cacheObj()->set(md5('wechat_menu_' . $data['userOpenId']), trim($data['Content']), 600);  //把手机号码存起来
            echo Wechat::textTpl($text);  //回复个菜单回去
            exit;
        }
    }

    /**
     * 执行菜单对应的函数
     */
    public function execMenu(){
        $menu = $this->menuInfo();
        if (!empty($menu)) {
            //查询手机号码
            $this->mobile = $this->cacheObj()->get(md5('wechat_menu_' . $this->getData('userOpenId')));  //获取手机号码
            if (empty($this->mobile)) {
                echo Wechat::textTpl('请先回复您的手机号码！');  //没有输入手机号码，或者缓存的手机号码过期了
                exit;
            }
            $function = $this->menuFix.$menu['function'];
            $this->$function();  //调用方法
            $this->sayNothing();
        }
        $menuNumber = $this->createMenuNumber();
        echo Wechat::textTpl('亲，您回复的序号暂时无法识别，当前可输入的序号为：' . implode('、', $menuNumber) . '，您也可以联系下客服进行处理！');
        exit;
    }

    /**
     * 检查是否回复的文字是菜单
     * @return int
     */
    public function isMenu(){
        $content = intval($this->getData('Content'));
        $result = preg_match('/^([0-9]{1,2})$/', $content);
        $menuNumber = $this->createMenuNumber();
        if($result && in_array($content, $menuNumber)){
            return true;
        }
        return false;
    }

    /**
     * 创建菜单列表文本
     * @return string
     */
    private function createMenuText(){
        $menuText = '';
        if (!empty($this->menu_array)) {
            foreach ($this->menu_array as $menu) {
                if (!empty($menu['number']) && !empty($menu['title']) && !empty($menu['function']) && method_exists($this, $this->menuFix.$menu['function'])) {
                    $menuText .= $menu['number'] . '. ' . trim($menu['title']) . "\n";
                }
            }
        }
        return trim($menuText);
    }

    /**
     * 创建菜单列表的所有数字选项
     * @return array
     */
    private function createMenuNumber(){
        $menuNumber = array();
        if (!empty($this->menu_array)) {
            foreach ($this->menu_array as $menu) {
                if (!empty($menu['number']) && method_exists($this, $this->menuFix.$menu['function'])) {
                    $menuNumber[] = $menu['number'];
                }
            }
        }
        return $menuNumber;
    }

    /**
     * 获取某个菜单的详情
     * @return array
     */
    private function menuInfo($number = 0){
        if($number <= 0){
            $number = intval($this->getData('Content'));
        }
        if (!empty($this->menu_array)) {
            foreach ($this->menu_array as $menu) {
                if (!empty($menu['number']) && method_exists($this, $this->menuFix.$menu['function']) && $menu['number'] == $number) {
                    return $menu;
                }
            }
        }
        return array();
    }

    /**
     * 实例化缓存对象 - Memcache缓存
     * @return null|Memcache
     */
    private function cacheObj() {
        if(is_null($this->Memcache)){
            $this->Memcache = new Memcache();
        }
        return $this->Memcache;
    }
/* ====================================================================菜单对应的执行函数原型 - Start================================================================================ */
    /**
     * 查询订单与快递信息
     */
    private function __sendMemberLogistics() {
        $data = $this->getDatas();
        $data['Content'] = $this->mobile;
        $Logistics = new \Common\Logic\Wechat\LogisticsObj();
        $Logistics->setDatas($data);
		$Logistics->setTexts($this->getTexts());
        $Logistics->send();
    }

    /**
     * 查询会员等级
     * @return null|Memcache
     */
    private function __sendMemberLevel() {
        $mobile = \Common\Extend\PhxCrypt::phxEncrypt($this->mobile);
        $user_id = D('Users')->where("(`mobile`='$mobile' OR `user_num`='$mobile') AND `state`!=9")->getField('user_id');
        $text = '亲，您还不是会员，现在注册成为韩国瓷肌会员吧，尽享更多会员福利。';
        if ($user_id > 0) {
            $rank = D('IntegralCenter')->getUserRank($user_id);  //获取会员等级
            if ($rank > 0) {
                $rank_name = D('UserRank')->where("rank_id = '$rank'")->getField('rank_name');
                $rank_name = $rank_name ? $rank_name : '未知级别';
            } else {
                $rank_name = D('UserRank')->order("min_points asc")->getField('rank_name');
            }
            $text = '你好，' . $this->mobile . '先生/女士，你现在是韩国瓷肌' . $rank_name . '。';
        }
        echo Wechat::textTpl($text);
        exit;
    }

    /**
     * 查询会员积分
     * @return null|Memcache
     */
    private function __sendMemberIntegral() {
        $mobile = \Common\Extend\PhxCrypt::phxEncrypt($this->mobile);
        $custom_id = D('Users')->where("(`mobile`='$mobile' OR `user_num`='$mobile') AND `state`!=9")->getField('custom_id');
        $text = '亲，您还不是会员，现在注册成为韩国瓷肌会员吧，尽享更多会员福利。';
        if ($custom_id > 0) {
            $result = D('IntegralCenter')->getPointsLeft(0, $custom_id);  //查询用户可用积分, 会计算被冻结的积分在内
            $user_points = $result['user_points'];
            $text = '你好，' . $this->mobile . '先生/女士，你现在有效积分是' . $user_points . '。';
        }
        echo Wechat::textTpl($text);
        exit;
    }
/* ====================================================================菜单对应的执行函数原型 - End================================================================================ */
}