<?php
namespace Home\Controller;

use Common\Controller\InitController;
use Common\Extend\Curl;
use Common\Logic\FavourableActivityLogic;

class IndexController extends InitController
{
    private $faceHost = 'http://img.api.cjlady.com';


    public function index()
    {
    }

    public function test(){
        if (!$this->openId) {
            $this->openId = I('request.openid', '');
            session('sopenid', $this->openId);
        }
        echo $this->openId;
    }

    public function face(){
        if(IS_POST){
            $params = I('param.');
            $key = md5(json_encode($params));
            $data = S($key);
            if(empty($data)){
                $url = $this->faceHost.'/FaceScore/index';
                $result = Curl::request($url,$params);
                $data = json_decode($result,true);
                S($key,$data);
                S($data['fid'],$data);
            }
        }else{
            $fid = I('param.fid',0,'intval');
            $data = S($fid);
        }
        if(!empty($data['img'])){
            $data['img'] = $this->faceHost.$data['img'];
        }
        echo json_encode($data);
    }

    public function demo(){
        $params = I('param.');
        $param['pic'][] = $params['pic'];
        $key = md5(json_encode($params));
        $data = S($key);
        if(empty($data)){
            $url = $this->faceHost.'/FaceScore/demo';
            $result = Curl::request($url,$param);
            $data = json_decode($result,true);
            S($key,$data);
        }
        $data['img'] = $this->faceHost.$data['img'];
        echo json_encode($data);
    }

    public function getImg(){
        $fid = cookie('fid');
        if(!empty($fid)){
            $data = S($fid);
            echo  $this->faceHost.$data['img'];
        }
    }
}

