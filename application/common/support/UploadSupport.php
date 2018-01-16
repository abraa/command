<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/12 16:47
 * ====================================
 * File: UploadSupport.php
 * ====================================
 */

namespace app\common\support;


use think\File;

class UploadSupport {

    protected static $validate = [];                    //图片验证规则


    /**
     *
     * size	上传文件的最大字节
    ext	文件后缀，多个用逗号分割或者数组
    type	文件MIME类型，多个用逗号分割或者数组

     */
    public static function upload(){
        $data = [];
        // 获取表单上传文件 例如上传了001.jpg
        $files = request()->file();
        if(!empty($files)){
            // 移动到框架应用根目录/public/uploads/ 目录下
            foreach($files as $key => $file){
                $validate = isset(static::$validate[$key]) ? static::$validate[$key] :[];
                if(is_array($file)){                                   //上传的是数组  <input type='file' name='image[]' />
                    foreach($file as $file2){
                        $res = static::uploadSave($file2,$validate);
                        if(false === $res){
                            return false;
                        }
                        $data[$key][] = $res ;
                    }
                }else{
                    $data[$key] = static::uploadSave($file,$validate);;
                }
            }
        }
        return $data;
    }

    protected static function uploadSave($file,$validate = []){
        $uploadPath = ROOT_PATH . 'public' . DS . 'uploads';
        if($file instanceof File){
            $info = $file->validate($validate)->move($uploadPath);
            if($info){
                return '/' . 'uploads'. '/' . str_replace('\\','/',$info->getSaveName());
            }else{
                abort(500, $file->getError());
            }
        }

        return false;
    }

    /**
     * 设置验证参数
     * @param string $name   验证名称  field上传字段名称    ['img'=>[size=>1234,ext=>[jpg,png],type=>[]]]
     * @param array $value
     */
    public static function setValidate($name='',$value=[]){
        if(is_array($name)){
            static::$validate = array_merge(static::$validate,$name);
        }else{
            static::$validate[$name] = $value;
        }
    }

    /**
     * base64上传图片
     * @param $base
     * @return mixed|string|void
     */
    public static function base64SaveImage($base){
        return base64ToImage($base);
    }
}