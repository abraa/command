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
               $filepath = PUBLIC_PATH.str_replace('_','/',$id);
           }
            $data =  fileList($filepath);
            return $data;
        }
        return $this->fetch($this->template);
    }

    /**
     *  删除
     */
    public function delete()
    {
        $path = input('path',null);
        if(empty($path)){
            $this->error('缺少参数');
        }
        $files = explode('|',$path);
        foreach($files as $file){
            fileRemove($file);
        }
        $this->success('删除成功');
    }
}