<?php
/**
 * ====================================
 * 会员中心商品控制器
 * ====================================
 * Author: 9006758
 * Date: 2017-04-12
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: GoodsCenterController.class.php
 * ====================================
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;
use Common\Extend\Time;

class GoodsCenterController extends CpanelController{
    protected $tableName = 'GoodsCenter';

    //对应旧商品原图片的基础 路径
//    protected $oldimg_path = 'http://my.chinaskin.cn/public/upload/goods/'; //前台
    protected $img_old_path = 'http://useradmin.chinaskin.cn/public/upload/goods/'; //后台
    protected $img_base_path = 'upload/ucenter/goods/';//商品原图片的上传目录

	/*
	 * 商品上下架
	 * @params sale_type int 类型 0-下架，1-上架
	 * @params item_id string 商品id
	 */
	public function onSale(){
		$sale_type = I('request.sale_type', 0, 'intval');
		$goods_id = I('request.item_id');
		$this->dbModel->where(array('goods_id'=>array('in', $goods_id)))->setField('is_on_sale', $sale_type);
		
		//管理员操作日志
		if($sale_type == 1){
			$log_msg = '上架商品';
		}else{
			$log_msg = '下架商品';
		}
		$this->logAdmin($log_msg, 0, array('params'=>'goods_id in('.$goods_id.')'));

		$this->success();
	}

    public function form(){

        $goods_id = I('goods_id', 0, 'intval');
        $info = $this->dbModel->where(array('goods_id'=>$goods_id))->find();

        if($info){
            //商品属性
            $attrs = D('GoodsAttrCenter')->where(array('goods_id'=>$goods_id))->field('goods_id,attr_id,attr_value')->select();
            $info['attribute'] = $attrs;

            //商品描述
            $desc = array();
            if(!empty($info['goods_desc'])){
                $goods_desc = unserialize($info['goods_desc']);
                foreach($goods_desc as $k=>$v){
                    if(file_exists(APP_ROOT.$this->img_base_path.$v)){
                        $desc[$k]['original'] = '/'.$this->img_base_path.$v;
                        $desc[$k]['thumb'] = '/'.$this->img_base_path.'thumb/'.$v;
                    }else{
//                        $desc[$k]['original'] = $this->img_old_path.$v;
//                        $desc[$k]['thumb'] = $this->img_old_path.'thumb/'.$v;
                        // 不存在本地资源的图片全部指向资源站
                        $desc[$k]['original'] = C('SOURCE_UCENTER_PATH').'goods/'.$v;
                        $desc[$k]['thumb'] = C('SOURCE_UCENTER_PATH').'goods/'.'thumb/'.$v;
                    }
                }
            }
            $info['goods_desc'] = $desc;

            //相册
            $gallery = D('GoodsGalleryCenter')->where(array('goods_id'=>$goods_id))->select();
            foreach($gallery as $key=>&$val){
                if(file_exists(APP_ROOT.$this->img_base_path.$val['img_url'])){
                    $val['original'] = '/'.$this->img_base_path.$val['img_url'];
                    $val['thumb'] = '/'.$this->img_base_path.'thumb/'.$val['img_url'];
                }else{
//                    $val['original'] = $this->img_old_path.$val['img_url'];
//                    $val['thumb'] = $this->img_old_path.'thumb/'.$val['img_url'];
                    // 不存在本地资源的图片全部指向资源站
                    $val['original'] = C('SOURCE_UCENTER_PATH').'goods/'.$val['img_url'];
                    $val['thumb'] = C('SOURCE_UCENTER_PATH').'goods/'.'thumb/'.$val['img_url'];

                }
            }
            $info['gallery'] = $gallery;
        }

        $this->assign('info', $info);
        $this->display();
    }

    public function save(){

        $goods_id = I('request.goods_id', 0, 'intval');//产品id
        $params['cat_id']       = I('request.cat_id', 0, 'intval'); //分类id
        $params['goods_name']   = I('request.goods_name', '', 'trim'); //商品名称
        $params['goods_sn']     = I('request.goods_sn', '', 'trim'); //商品货号
        $params['keyword']      = I('request.keyword', '', 'trim'); //关键字
        $params['market_price'] = I('request.market_price', '', 'floatval'); //市场价
        $params['shop_price']   = I('request.shop_price', '', 'floatval'); //销售价
        $params['goods_brief']  = I('request.goods_brief', '', 'trim'); //商品简述
        $params['attr_id']      = I('request.attr_id');
        $params['attr_value']      = I('request.attr_value');

        if($goods_id){
            //更新
            $log_msg = '编辑商品';
            $params['last_update'] = Time::gmTime();
            $this->dbModel->where(array('goods_id'=>$goods_id))->save($params);
        }else{
            //添加
            $log_msg = '添加商品';
            $params['add_time'] = Time::gmTime();
            $goods_id = $this->dbModel->data($params)->add();
        }

        //先删除原先的商品属性，后在重新添加商品属性
        if(!empty($params['attr_id'][0]) && !empty($params['attr_value'][0])){
            $goodsAttrModel = D('GoodsAttrCenter');
            foreach($params['attr_id'] as $k=>$v){
                if(empty($v['attr_id']) || empty($v['attr_value'])){
                    continue;
                }
                $data_attr[$k]['goods_id'] = $goods_id;
                $data_attr[$k]['goods_sn'] = $params['goods_sn'];
                $data_attr[$k]['attr_id'] = $v;
                $data_attr[$k]['attr_value'] = $params['attr_value'][$k];
            }
            $goodsAttrModel->where(array('goods_id'=>$goods_id))->delete();
            $goodsAttrModel->addAll($data_attr);
        }

        if(!empty($_FILES['gallery']['name'][0]) || !empty($_FILES['goods_desc']['name'][0])){
            $this->imgUpload($_FILES, $goods_id, I('request.img_desc'));
        }
        //操作日志
        $this->logAdmin($log_msg, 0, array('params'=>'goods_id='.$goods_id));

        $this->ajaxReturn(array(
            'status'=>1,
            'info'=>'保存成功',
        ));
    }

    /**
     * 商品描述和相册图片处理
     * @param array $files
     * @param $goods_id
     */
    protected function imgUpload($files = array(),$goods_id,$img_desc = array()){

        $config = array(
            'maxSize' => 2097152, //2M
            'rootPath' => APP_ROOT.$this->img_base_path,
            'savePath' => $goods_id,
            'autoSub' => true,
            'subName' => $goods_id,
            'saveName' => array('makeRandName', ''),
            'exts' => array('jpg', 'gif', 'png', 'jpeg'),
        );
        $uploadClass = new \Think\Upload($config);// 实例化上传类
        if(!file_exists(APP_ROOT.$this->img_base_path)){
            makeDir(APP_ROOT.$this->img_base_path);
        }
        $info = $uploadClass->upload();
        if(!$info){
            $this->error($uploadClass->getError());
        }

        $where['goods_id'] = $goods_id;
        $goods_desc = $this->dbModel->where($where)->getField('goods_desc');
        if($goods_desc){
            $goods_desc = unserialize($goods_desc);
        }
        $data_gallery = array();
        $image = new \Think\Image();
        foreach($info as $key=>$val){
            if($val['key'] == 'gallery'){//相册

                //处理相册描述
                foreach($_FILES['gallery']['name'] as $k=>$v){
                    if($v == $val['name']){
                        $desc = isset($img_desc[$k]) ? $img_desc[$k] : '';
                        break;
                    }
                }
                $data_gallery[] = array(
                    'goods_id' => $goods_id,
                    'img_url' => $goods_id.'/'.$val['savename'],
                    'img_desc' => $desc,
                );

            }else if($val['key'] == 'goods_desc'){
                //商品描述
                $goods_desc[] = $goods_id.'/'.$val['savename'];
            }

            //生成缩略图
            $img_path = APP_ROOT.$this->img_base_path.$goods_id.'/'.$val['savename'];
            $thumb_path = APP_ROOT.$this->img_base_path.'thumb/'.$goods_id.'/';
            if(!file_exists($thumb_path)){
                makeDir($thumb_path);
            }
            if(file_exists($img_path)){
                $image->open($img_path);
                $width = $image->width(); // 原图片的宽度
                $height = $image->height(); // 原图片的高度
                $dst_height = 240;
                $dst_width = $dst_height / $height * $width;
                $image->thumb($dst_width, $dst_height)->save($thumb_path.$val['savename']);
            }
        }

        //更新商品描述
        if(!empty($goods_desc)){
            $this->dbModel->where($where)->setField('goods_desc', serialize($goods_desc));
        }

        //相册入库
        if(!empty($data_gallery)){
            D('GoodsGalleryCenter')->data($data_gallery)->addAll($data_gallery);
        }
    }

    /**
     * 删除相册图片
     */
    public function galleryRmove(){
        $img_id = I('request.imgId', 0, 'intval');
        $goods_id = I('request.goodsId', 0, 'intval');
        $galleryModel = D('GoodsGalleryCenter');
        $img_url = $galleryModel->where(array('img_id'=>$img_id,'goods_id'=>$goods_id))->getField('img_url');
        $res = $galleryModel->where(array('img_id'=>$img_id,'goods_id'=>$goods_id))->delete();
        //删除日志
        if($res){
            $this->unlinkImg($img_url);
            $this->logAdmin('删除商品相册', 0, array('params'=>"img_id=$img_id and goods_id=$goods_id"));
        }
        $this->success();
    }

    /**
     * 商品
     */
    public function descRemove(){
        $desc_key = I('request.descKey');
        $goods_id = I('request.goodsId', 0, 'intval');

        $where['goods_id'] = $goods_id;
        $goods_desc = $this->dbModel->where($where)->getField('goods_desc');
        $goods_desc = unserialize($goods_desc);
        if(isset($goods_desc[$desc_key])){
            $desc_img = $goods_desc[$desc_key];
            unset($goods_desc[$desc_key]);
        }
        $goods_desc_res = empty($goods_desc) ? '' : serialize($goods_desc);
        $res = $this->dbModel->where($where)->setField('goods_desc', $goods_desc_res);
        //删除日志
        if($res){
            $this->unlinkImg($desc_img);
            $this->logAdmin('删除商品描述', 0, array('params'=>"img_key=$desc_key and goods_id=$goods_id"));
        }
        $this->success();
    }

    /**
     * 属性删除
     * @params goodId int 商品goods_id
     * @params attrId int 属性id
     */
    public function rmGoodAttr(){
        $goods_id = I('request.goodId', 0, 'intval');
        $attr_id = I('request.attrId', 0, 'intval');
        $res = D('GoodsAttrCenter')->where(array('goods_id'=>$goods_id, 'attr_id'=>$attr_id))->delete();
        if($res){
            //操作日志
            $this->logAdmin('删除属性', 0, array('params'=>"goods_id=$goods_id and attr_id=$attr_id"));

            $this->success();
        }
        else{
            $this->error('删除失败');
        }
    }

    /**
     * 删除相册图片和描述图片
     * @param $img string 图片路径 683/1492198465300606784.jpg
     */
    protected function unlinkImg($img){
        if(file_exists(APP_ROOT.$this->img_base_path.$img)){
            @unlink(APP_ROOT.$this->img_base_path.$img);
        }
        if(file_exists(APP_ROOT.$this->img_base_path.'thumb/'.$img)){
            @unlink(APP_ROOT.$this->img_base_path.'thumb/'.$img);
        }
    }
}