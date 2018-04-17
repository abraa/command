<?php
/**
 * ====================================
 * 商品评论相关数据模型
 * ====================================
 * Author: 9006758
 * Date: 2017-04-17
 * ====================================
 * File: CommentsModel.class.php
 * ====================================
 */
namespace Home\Model;
use Common\Model\CommonModel;
use Common\Extend\Time;

class CommentsModel extends CommonModel{
    /**
     * 获取商品评论
     * @param $goods_id 商品id，多个ID之间逗号隔开
     * @param $get_type 标签分类id，0-不区分
     * @param $page 页码
     * @param $limit 每页数量
     * @param $mode 数据类型，0=所有评论，1=晒单（有图片）的评论，2=有追加评论
     * return array;
     */
    public function getComments($goods_id, $get_type=0, $page=0, $limit=3, $order_by='',$mode = 0){
        if($goods_id == ''){
            return array();
        }
        $where[] = "c.id_value IN($goods_id)";
        $where[] = 'c.status = 0';
        $where[] = 'c.show_status = 1';

        if($get_type>0){
            $where[] = "c.type_id = '$get_type'";
        }

        $order = 'c.id DESC';
        if(!empty($order_by)){
            $order = $order_by.','.$order;
        }
        $field = 'c.id,c.id_value,c.user_name,c.show_time,c.content,c.show_time,c.level,c.is_client,c.pic,c.pic1,c.like_num,c.z_content,c.z_date,c.z_review_time';
        if($mode == 1){  //晒单（有图片）的评论
            $where[] = "(cg.img_url != '' OR c.pic != '' OR c.pic1 != '')";
            $good_comments = $this->field($field)
                ->alias('c')
                ->join("LEFT JOIN __COMMENTS_GALLERY__ AS cg ON cg.comment_id = c.id")
                ->where(implode(' and ',$where))
                ->limit($page, $limit)
                ->group('c.id')
                ->order($order)
                ->select();
        }else{  //有追加评论 or 所有评论
            if($mode == 2){  //有追加评论
                $where[] = "c.z_content != '' and c.z_review_time > 0";
            }
            $good_comments = $this->field($field)
                ->alias('c')
                ->where(implode(' and ',$where))
                ->limit($page, $limit)
                ->group('c.id')
                ->order($order)
                ->select();
        }
        
        if(!empty($good_comments)){
            $source_domain = C('domain_source.img_domain');

            //评论的图片是由Q站站上传的，不存咋3g，所以有以下替换操作
            if(preg_match('/\/3g\//', $source_domain)){
                $source_domain = str_replace('/3g/', '/q/', $source_domain);
            }

            $like_key = cookie('like_key');
            if(empty($like_key)){
                $like_key = session_id();
            }
            cookie('like_key',$like_key,array('expire'=>time()+3600*24*365));

            $CommentsGalleryModel = D('CommentsGallery');
            foreach($good_comments as $key=>$val){
                if(!empty($val['show_time'])){
                    //计算追加的天数
                    $val['z_day'] = 0;
                    if($val['z_review_time'] > 0){
                        if(!empty($val['z_date'])){
                            $z_time = $val['z_date'];
                            $val['z_date'] = date('Y-m-d H:i:s',$val['z_date']);
                            $val['z_day'] = floor(($z_time - $val['show_time']) / 86400);
                        }
                    }else{
                        $val['z_date'] = 0;
                        $val['z_content'] = '';
                    }

                    $val['show_time'] = date('Y-m-d', $val['show_time']);
                }

                $val['is_like'] = $this->isLike($like_key,$val['id']);
                $val['is_like'] = $val['is_like'] ? 1 : 0;

                //处理晒图
                $good_comments[$key] = $CommentsGalleryModel->getCommentImg($val, $source_domain);
            }
        }
        return $good_comments;
    }

    /**
     * 校验评论是否点过赞
     * @param $key
     * @param $comment_id
     * @return bool
     */
    public function isLike($key,$comment_id){
        if(empty($key) || empty($comment_id)){
            return;
        }
        return M('CommentLike')->where(array('key'=>$key,'comment_id'=>$comment_id))->getField('id');
    }

    /**
     * 评论点赞
     * @param $key
     * @param $comment_id
     * @return bool
     */
    public function addLike($key,$comment_id){
        $data = array(
            'key'=>$key,
            'ip'=>get_client_ip(),
            'creat_time'=>time(),
            'comment_id'=>$comment_id,
        );
        M('CommentLike')->where(array('key'=>$key,'comment_id'=>$comment_id))->add($data);
        $this->where(array('comment_id'=>$comment_id))->setInc('like_num',1);  //点赞数+1
        return true;
    }
}