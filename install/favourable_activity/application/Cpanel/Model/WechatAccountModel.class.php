<?php
/**
 * ====================================
 * 微信公众帐号模型
 * ====================================
 * Author: 9004396
 * Date: 2017-02-22 14:19
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: WechatAccountModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelModel;

class WechatAccountModel extends CpanelModel{

    protected $_validate = array(
        array('text','require','{%text_lost}'),
        array('token','require','{%token_lost}'),
        array('app_id','require','{%app_id_lost}'),
        array('app_secret','require','{%app_secret_lost}'),
        array('encoding_aes_key', 'isAesKey', '{%aes_key_lost}', self::MUST_VALIDATE, 'callback'),
    );

    protected function isAesKey($value){
        $params = I('post.');
        if($params['crypted'] > 0 && empty($value)){
            return false;
        }
        return true;
    }

    public function filter($params){
        $where = array();
        if($params['keyword']){
            $where['text'] = array('LIKE', "%{$params['keyword']}%");
        }
        return $this->where($where);
    }

    /**
     * 获取帐号的app_id和密钥
     * @param $id
     * @return mixed
     */
    public function getAccountKey($id){
        return D('WechatAccount')->field('app_id,app_secret')->where(array('id'=>$id))->find();
    }

    /**
     * 获取下拉菜单列表数据
     * @param $params
     * @return array|mixed
     */
    public function selectTree($params){
        $where = array();
        if(isset($params['check_data_power'])){
            $account_id = getDataPower(login('user_id'), 'WechatAccount');
            if(!empty($account_id)){
                $where['id'] = array('IN',$account_id);
            }
        }
        //获取数据权限
        $list = $this->field('id,text')->where($where)->select();

        if(!empty($list)){
            if(isset($params['selected'])){
                foreach($list as $key=>$value){
                    if($params['selected'] == $value['id']){
                        $list[$key]['selected'] = true;
                    }
                }
            }
            $list = array_merge(array(
                0=>array(
                    'id'=>0,
                    'text'=>'请选择公众号',
                )
            ),$list);

        }
        return $list;
    }
}