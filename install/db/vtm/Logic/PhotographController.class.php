<?php
/**
 * ====================================
 * 图片合成 - 图片打印
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-08-03 15:44
 * ====================================
 * File: PhotographController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;
use Common\Extend\Curl;
use Think\Cache;

class PhotographController extends InitController {
    public function index(){
        send_http_status(404);
    }

    /**
     * 合成照片 - 打印合成后的照片
     */
    public function printPicture() {
        $base64_image = I('post.image','','trim');
        $key = I('request.key','','trim');  //redis缓存的key   测试：50cc5dceca57ad5567d198c7c8e3f457
        //$base64_image = file_get_contents(APP_ROOT . 'base64');
        $get_image_point = I('post.get_image_point',0,'intval');  //1=只返回照片定位点，0=打印图片

        if(empty($base64_image)){
            $this->error('请上传图片！');
        }
        if(empty($key)){
            $this->error('您的操作有误！');
        }

        //获取颜值分数
        $score = 0;  //照片分数
        $params = array(
            'source'=>getDomain(),
            'base64'=>$base64_image,
        );
        $result = Curl::post('http://img.api.cjlady.com/Api/Face/doUpload/add_points/0', $params);
        if(empty($result)){
            $this->error('照片识别出错了！');
        }
        $result = json_decode($result, true);
        if(isset($result['status']) && $result['status'] == 1){  //请求成功
            if($get_image_point > 0){
                $point = isset($result['result']['result']['item'][0]['landmarks']) ? $result['result']['result']['item'][0]['landmarks'] : array();
                $this->success($point);
                exit;
            }
            $score = intval($result['score']);  //由于带有小数点，必须去掉
            $score = $score<70 ? rand(70, 80) : $score;  //最小50分
        }else{
            $this->error((isset($result['msg']) ? $result['msg'] : '照片识别出错了！'));
        }

        $redis = Cache::getInstance("redis",C('REDIS'));
        $data = $redis->get($key);
        $openid = isset($data['openid']) ? $data['openid'] : '';
        $counter = isset($data['machine']) ? $data['machine'] : '';  //机器码, 测试：2
        if(empty($openid) || empty($counter)){
            $this->error('您的操作已超时或不正确，请重试！');
        }

        //处理base64图片，保存到临时路径，之后会删除掉
        $tmp_image_path = '';
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image, $result)){
            $tmp_image_path = RUNTIME_PATH . 'Temp/P' . date('YmdHis').rand(1000000,9999999);
            $image_name_suffix = '.' . ($result[2] == 'jpeg' ? 'jpg' : $result[2]);
            $tmp_image_path = $tmp_image_path . $image_name_suffix;
            @file_put_contents($tmp_image_path, base64_decode(str_replace($result[1], '', $base64_image)));
            if(!file_exists($tmp_image_path)){
                $this->error('图片保存失败！');
            }
        }else{
            $this->error('图片内容错误！');
        }

        //合成图片
        $Photograph = new \Common\Logic\Photograph\Photograph();
        $Photograph->setParam('image_backgroup_url', APP_PATH . 'Common/Logic/photograph/backgroup.jpg');
        $Photograph->setParam('photo_url', array(
            $tmp_image_path,
            APP_PATH . 'Common/Logic/photograph/bottom.png',
        ));
        $Photograph->setParam('image_locate', array(
            array(0,0),
            array(0,804),
        ));
        $Photograph->setParam('image_size', array(
            array(804,804),
            array(),
        ));
        $Photograph->setParam('save_path', APP_ROOT . 'uploads/printPicture/'.date('Y-m-d').'/');
        $photo_url = $Photograph->composeThree();
        if(!file_exists(APP_ROOT . $photo_url)){
            $this->error('图片合成失败!');
        }
        unlink($tmp_image_path);

        $image_url = getDomain() . $photo_url;

        //测试
        /*
        $this->success(array(
            'image_url'=>$image_url,
        ));
        exit;
        */
        //用openid获取unionid
        $unionid = \Common\Extend\Wechat\Wechat::getUserInfo($openid, 'unionid');
        $unionid = !empty($unionid) ? $unionid : '';

        //通知打印图片
        $DeepstreamRequest = new \Common\Logic\DeepstreamRequest();
        $response = $DeepstreamRequest->printPicture($counter, $unionid, $image_url, $score);
        if(!isset($response->code)){
            $this->error('系统繁忙,请重试!');
        }

        if($response->code == 0){  //打印成功
            $this->success(array(
                'image_url'=>$image_url,
            ));
        }else if($response->code == 3){  //无可用打印次数
            $this->error((isset($response->msg) && !empty($response->msg) ? $response->msg : '无可用打印次数！'));
        }else{  //其他错误
            $this->error('系统繁忙，请重试！');
        }
    }
}