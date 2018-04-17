<?php
/**
 * ====================================
 * 会员中心套装商品控制器
 * ====================================
 * Author: 9006758
 * Date: 2017-04-19
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: PackageGoodsCenterController.class.php
 * ====================================
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;

class PackageGoodsCenterController extends CpanelController{
    protected $tableName = 'PackageGoodsCenter';
	protected $save_path = 'upload/ucenter/goods/'; //套装描述图片保存路径

    public function form(){

        $act_id = I('request.act_id', 0, 'intval');
        $info = $this->dbModel->getPackageInfo($act_id);
		
        $this->assign('info', $info);
        $this->display();
    }

    public function save(){
        $data = I('request.');
		
        $act_id = $this->dbModel->dataSave($data);
		if(!empty($_FILES['act_desc']['name'][0]) && $act_id){
			$imgs = $this->makeImg($_FILES, $act_id);
		}
		if(intval($data['act_id'])){
			$this->logAdmin('编辑套装', 0, array('params'=>'act_id='.$act_id));
		}else{
			$this->logAdmin('添加套装', 0, array('params'=>'act_id='.$act_id));
		}
		$this->ajaxReturn(array(
            'status'=>1,
            'info'=>'保存成功',
        ));
    }
	
	//保存图片
	public function makeImg($files, $act_id){
		$config = array(
            'maxSize' => 2097152, //2M
            'rootPath' => APP_ROOT.'upload',
            'savePath' => '/ucenter/goods/',
            'autoSub' => true,
            'subName' => $act_id.'_package',
            'saveName' => array('makeRandName', ''),
            'exts' => array('jpg', 'gif', 'png', 'jpeg'),
        );
        $uploadClass = new \Think\Upload($config);// 实例化上传类
		$info = $uploadClass->upload();
        if(!$info) $this->error($uploadClass->getError());
		
		$where['act_id'] = $act_id;
		$act_desc = $this->dbModel->where($where)->getField('act_desc');
        if($act_desc) $act_desc = unserialize($act_desc);
		$new_act_desc = array();
		$image = new \Think\Image();
		$thumb_path = APP_ROOT.$this->save_path.'thumb/'.$act_id.'_package/';
		if(!file_exists($thumb_path)){
			makeDir($thumb_path);
		}
		foreach($info as $val){
			$new_act_desc[] = $act_id.'_package/'.$val['savename'];
			
			//生成缩略图
			$original = APP_ROOT.$this->save_path.$act_id.'_package/'.$val['savename'];
			if(file_exists($original)){
                $image->open($original);
                $width = $image->width(); // 原图片的宽度
                $height = $image->height(); // 原图片的高度
                $dst_height = 240;
                $dst_width = $dst_height / $height * $width;
                $image->thumb($dst_width, $dst_height)->save($thumb_path.$val['savename']);
            }
		}
		if(!$act_desc) $act_desc = array();
		$act_desc = array_merge($act_desc, $new_act_desc);
		$act_desc_str = serialize($act_desc);
		$res = $this->dbModel->where(array('act_id'=>$act_id))->setField('act_desc', $act_desc_str);
		//更新失败，删除图片
		if(!$res && !empty($new_act_desc)){
			$this->imgUnlink($new_act_desc);
		}
		return $act_desc_str;
	}
	
	//图片删除
	protected function imgUnlink($img_arr = array()){
		if(!empty($img_arr)){
			foreach($img_arr as $val){
				if(file_exists('/'.$this->save_path.$val)){
					@unlink('/'.$this->save_path.$val);
				}
			}
		}
	}

    //搜索商品
    public function getGoods(){
        $act_id = I('request.act_id', 0, 'intval');
        $keyword = I('request.keyword', '', 'trim');
        $goodsModel = D('GoodsCenter');
        $goods = array();
        if($act_id){
            $where['pg.package_id'] = $act_id;
            $goods = $goodsModel->alias('g')
                ->join("__PACKAGE_GOODS__ as pg on g.goods_id=pg.goods_id")
                ->where($where)
                ->field('g.goods_id,g.goods_sn,g.goods_name,g.market_price,g.shop_price,pg.goods_number')
                ->select();
        }else{
//            if($keyword){
                $condition['goods_name'] = array('like', "%$keyword%");
                $condition['goods_sn'] = $keyword;
                $condition['_logic'] = 'OR';
                $where['_complex'] = $condition;
                $where['is_on_sale'] = 1;
                $where['goods_type'] = 1;
                $goods = $goodsModel->where($where)->field('goods_id,goods_sn,goods_name,market_price,shop_price')->select();
//            }
        }

        $this->ajaxReturn($goods);
    }

	//套装描述图片删除
    public function descRemove(){
        $act_id = I('request.act_id', 0, 'intval');
		$key = I('request.descKey');
		
		$where['act_id'] = $act_id;
		$act_desc = $this->dbModel->where($where)->getField('act_desc');
		$act_desc = unserialize($act_desc);
		if(!empty($act_desc)){
			if(file_exists(APP_ROOT.$this->save_path.$act_desc[$key])){
				@unlink(APP_ROOT.$this->save_path.$act_desc[$key]);
			}
			if(file_exists(APP_ROOT.$this->save_path.'thumb/'.$act_desc[$key])){
				@unlink(APP_ROOT.$this->save_path.'thumb/'.$act_desc[$key]);
			}
			unset($act_desc[$key]);
		}
		$act_desc = empty($act_desc) ? '' : serialize($act_desc);
		$this->dbModel->where($where)->setField('act_desc', $act_desc);

        //操作日志
        $this->logAdmin('删除套装描述', 0, array('params'=>"act_id=$act_id"));    //操作日志
		$this->success();
    }
	
	public function delete(){
		$item_id = I('request.item_id', 0, 'trim');
        if(!empty($item_id)){
            $res = $this->dbModel->where(array('act_id' => array('in', $item_id)))->delete();

            /* 删除关联关系 */
            if($res){
                M('package_goods', null, 'USER_CENTER')->where(array('package_id' => array('in', $item_id)))->delete();
            }
            //操作日志
            $this->logAdmin(L('DELETE') . L('SUCCESS'), 0, array('params'=>"act_id in($item_id)"));    //操作日志

            $this->success(L('DELETE') . L('SUCCESS'));
        }
        $this->error('请选择需要操作的选项！');
	}

}