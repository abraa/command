<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/2/26 11:35
 * ====================================
 * File: File.php
 * ====================================
 */

namespace app\admin\controller;

use app\admin\BaseController;

class File extends BaseController
{

    /**
     * 列表
     * @return array|mixed
     */
    public function index(){
        if ($this->request->isAjax()) {
            $filepath = PUBLIC_PATH .'uploads';
            $id = input('id',null);
           if(!empty($id)){
               $filepath = PUBLIC_PATH.str_replace(config('SPLIT_FILE'),DS,$id);
           }
            $data =  listFile($filepath,PUBLIC_PATH,false);
            return $data;
        }
        return $this->fetch($this->template);
    }

    /**
     *  删除
     */
    public function delete()
    {
        $id = input('id',null);
        if(empty($id)){
            $this->error('缺少参数');
        }
        $files = explode('|',$id);
        foreach($files as $file){
            $file = PUBLIC_PATH.str_replace(config('SPLIT_FILE'),DS,$file);
            removeFile($file);
        }
        $this->success('删除成功');
    }

    /**
     * 上传文件 返回文件路径
     */
    public function upload(){
        LoginSupport::getUserId();  //检查用户是否登录
        $res = UploadSupport::upload();
        if(!$res){
            $this->error('上传失败');
        }else{
            $this->success('上传成功','',$res);
        }
    }

}