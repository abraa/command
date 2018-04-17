<?php
/**
 * ====================================
 * 微信自动回复二维码模型
 * ====================================
 * Author: 9009123
 * Date: 2017-08-14 15:00
 * ====================================
 * File: WechatUpqrcodeModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelModel;

class WechatUpqrcodeModel extends CpanelModel{
    //标识符，类似于分类，供前台识别
    public $identifier = array(
        'spread'=>'推广',  //推广用的页面
    );

    public function filter($parmas = array()){
        $where = array();
        if(isset($parmas['locked'])){
            $where['locked'] = $parmas['locked'];
        }

        if(isset($parmas['account_id'])){
            $where['account_id'] = $parmas['account_id'];
        }
        $this->where($where);

        return $this;
    }


    public function grid($params = array()){
        $orderBy = isset($params['sort']) ? trim($params['sort']) . ' ' .  trim($params['order']) : '';
        $orderBy = empty($orderBy) ? 'update_time desc,create_time desc' : $orderBy;
        $page = isset($params['page']) && $params['page'] > 0 ? intval($params['page']) : 1;
        $pageSize = isset($params['rows']) && $params['rows'] > 0 ? intval($params['rows']) : 10;

        //统计总记录数
        $options = $this->options;
        $total = $this->count();

        //排序并获取分页记录
        $options['order'] = empty($options['order']) ? $orderBy : $options['order'];
        $this->options = $options;
        $this->limit($pageSize)->page($page);
        $rows = $this->getAll();
        if(!empty($rows)){
            $rows = $this->info($rows);
        }
        return array('total' => (int)$total, 'rows' => (empty($rows) ? false : $rows), 'pagecount' => ceil($total / $pageSize));
    }

    /**
     * 处理详情
     * @param array $data
     * @return array
     */
    public function info($data = array()){
        if(!empty($data)){
            foreach($data as $key=>$value){
                $value['identifier_text'] = isset($this->identifier[$value['identifier']]) ? $this->identifier[$value['identifier']] : $value['identifier'];
                $value['show_thumb'] ='<a href="javascript:;" file_path="'.$value['file_path'].'" class="list_show_thumb easyui-linkbutton"><span style="font-size:14px;" class="fa fa-eye"> </span></a>';
                $data[$key] = $value;
            }
        }
        return $data;
    }
}