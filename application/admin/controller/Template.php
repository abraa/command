<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/3/9 14:59
 * ====================================
 * File: Template.php
 * ====================================
 */

namespace app\admin\controller;


use app\admin\BaseController;

class Template extends BaseController{

    /**
     * 列表
     * @return array|mixed
     */
    public function index(){
        if ($this->request->isAjax()) {
            $filepath = ROOT_PATH .'template';
            $id = input('id',null);
            if(!empty($id)){
                $filepath = $filepath.str_replace(config('SPLIT_FILE'),DS,$id);
            }
            $data =  listFile($filepath,ROOT_PATH .'template',true);
            return $data;
        }
        return $this->fetch($this->template);
    }

    /**
     * 表单入口
     */
    public function form()
    {
        if ($this->request->isPost()) {
            $this->save();
        }
        $type = input('type','dir','trim');   //默认目录
        if('file'== $type){
            $template = 'form_file';
        }else{
            $template = 'form_dir';
        }
        $id = input('id',null);         //有id根据id判断是文件还是目录(修改才会有id)
        if(!empty($id)){
            $filepath = ROOT_PATH .'template';
            $filepath = $filepath.str_replace(config('SPLIT_FILE'),DS,$id);
            if(is_file($filepath)){
                $template ="form_file";
                $content = file_get_contents($filepath);
                $this->assign('content',$content);

            }else{
                $template = "form_dir";
            }
        }
        return $this->fetch($template);
    }

    /**
     * 提交保存
     */
    public function save()
    {
        $path = input('path');
        $id = input('id');
        $name = input('text');
        $type = input('type','dir','trim');   //默认目录
        if(empty($id)){             //添加
            //1.生成文件
            if(!is_dir($path)){
                $this->error('该路径不存在!');
            }
            if(empty($name)){
                $this->error('文件名不能为空!');
            }
            $filepath = $path.DS.$name;                                         //新文件路径
        }
        else{                      //修改
            $filepath = ROOT_PATH .'template';
            $filepath = $filepath.str_replace(config('SPLIT_FILE'),DS,$id);
            if(!file_exists($filepath)){
                $this->error('文件不存在!');
            }
            $oldfile = $filepath;               //旧文件名
            $path = substr($filepath,0,strrpos($filepath,DS));              //取原文件目录
            $filepath = $path.DS.$name;                                         //新文件路径
            @rename($oldfile,$filepath);                                        //重命名
        }
        //保存文件
        if('file'==$type){
            $content = input('content','');
            if(!writeFile($filepath,$content)){
                $this->error('保存文件失败');
            }
        }else{
            if(!makeDir($filepath)){
                $this->error('创建目录失败!');
            }
        }
        $this->success("保存成功");
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
            $file = ROOT_PATH .'template'.str_replace(config('SPLIT_FILE'),DS,$file);
            removeFile($file);
        }
        $this->success('删除成功');
    }
}