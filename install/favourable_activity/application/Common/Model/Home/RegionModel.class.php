<?php
/**
 * ====================================
 * 地区模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-07 17:41
 * ====================================
 * File: RegionModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\UserCenterModel;

class RegionModel extends UserCenterModel{
    /**
     * 根据ID获取地区
     * @param array $regionId
     * @return array|mixed
     */
	public function getList($regionId = array()){
        $data = $this->field('region_id,region_name')->where(array('region_id'=>array('IN',$regionId)))->select();
        return !empty($data) ? $data : array();
    }
}