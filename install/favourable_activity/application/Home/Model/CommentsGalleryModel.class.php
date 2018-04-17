<?php
/**
 * ====================================
 * 商品评论相册相关数据模型
 * ====================================
 * Author: 9006758
 * Date: 2017-04-18
 * ====================================
 * File: CommentsGalleryModel.class.php
 * ====================================
 */
namespace Home\Model;
use Common\Model\CommonModel;
use Common\Extend\Time;

class CommentsGalleryModel extends CommonModel{

    /**
     * 获取评论图片
     * @param array $comments_info 评论详情
     * @param string $source_domain 资源域名
     * @return array
     */
    public function getCommentImg($comments_info = array(), $source_domain = ''){
        $comments_info['base_img'] = array();  //普通图片
        $comments_info['gallery_img'] = array();  //追加图片
        if(!empty($comments_info['pic'])){  //如果自身有设置图片则加入到普通图片
            $comments_info['base_img'][] = array('img_url'=>$source_domain.$comments_info['pic'],'thumb_url'=>$source_domain.$comments_info['pic']);  //普通图片
        }
        if(!empty($comments_info['pic1'])){  //如果自身有设置图片则加入到普通图片
            $comments_info['base_img'][] = array('img_url'=>$source_domain.$comments_info['pic1'],'thumb_url'=>$source_domain.$comments_info['pic1']);  //普通图片
        }
        unset($comments_info['pic'],$comments_info['pic1']);
        $img_data = $this->field("img_url,thumb_url,type")
            ->where("comment_id = '$comments_info[id]'")
            ->select();
        if(!empty($img_data)){
            foreach($img_data as $v){
                $img_type = $v['type'];
                unset($v['type']);
                $v['img_url'] = empty($v['img_url']) ? '' : $source_domain.$v['img_url'];
                $v['thumb_url'] = empty($v['thumb_url']) ? '' : $source_domain.$v['thumb_url'];
                if($img_type == 1){  //追加的晒图
                    $comments_info['gallery_img'][] = $v;
                }else{
                    $comments_info['base_img'][] = $v;
                }
            }
        }
        return $comments_info;
    }
}