<?php
/**
 * ====================================
 * 支付 - 第三方平台
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-26 16:59
 * ====================================
 * File: Payment.class.php
 * ====================================
 */
namespace Common\Logic\Photograph;
use \Think\Image;

class Photograph{
    /**
     * 文字
     * @var null
     */
    private $text = '';
    /**
     * 文字大小
     * @var int
     */
    private $text_font_size = 14;
    /**
     * 文字颜色
     * @var string
     */
    private $text_color = '#00000000';
    /**
     * 字体文件路径
     * @var string
     */
    private $ttf_url = 'STXINGKA.TTF';
    /**
     * 被合成的图片路径
     * @var string
     */
    private $photo_url = array(
        'picture.png'
    );
    /**
     * 被合成的图片坐标x,y
     * @var array
     */
    private $image_locate = array(
        array(15, 30)
    );
    /**
     * 被合成的文字坐标x,y
     * @var array
     */
    private $text_locate = array(
        20, 520
    );
    /**
     * 多张图合成的大小
     * @var array
     */
    private $image_size = array(
        array(804,804)
    );
    /**
     * 合成图片背景
     * @var string
     */
    private $image_backgroup_url = 'Common/Logic/photograph/backgroup.jpg';
    /**
     * 图片临时保存路径
     * @var int
     */
    private $tmp_save_path = 'Temp/';
    /**
     * 图片保存路径
     * @var int
     */
    private $save_path = 'uploads/printPicture/';

    public function __construct(){
        $this->save_path = APP_ROOT . $this->save_path;
        $this->tmp_save_path = RUNTIME_PATH . $this->tmp_save_path;
    }

    /**
     * 三张图片合成
     * @return string
     */
    public function composeThree(){
        $image_name = time().rand(100000,999999);
        $image = new Image();

        makeDir($this->save_path);
        makeDir($this->tmp_save_path);

        $photo_url = $this->photo_url;
        foreach($photo_url as $key=>$url){
            $info = getimagesize($url);
            $type = image_type_to_extension($info[2], false);
            $type = $type=='jpeg' ? 'jpg' : $type;
            $tmp_path = RUNTIME_PATH . 'Temp/'.$image_name . rand(10000,99999).'.'.$type;
            if(!empty($this->image_size[$key])){
                //处理被合并的图片大小
                $image->open($url);
                $image->crop($image->width(), $image->height(), 0, 0, $this->image_size[$key][0], $this->image_size[$key][1]);
                $image->save($tmp_path);
            }else{
                $result = copy($url, $tmp_path);
                if($result === false){
                    $content = file_get_contents($url);
                    @file_put_contents($tmp_path, $content);
                }
            }
            $photo_url[$key] = $tmp_path;
        }
        $image->open($this->image_backgroup_url);
        foreach($photo_url as $key=>$url){
            $image->water($url, $this->image_locate[$key], 100);
            unlink($url);
        }
        if(!empty($this->text)){
            $image->text($this->text, $this->ttf_url, $this->text_font_size, $this->text_color, $this->text_locate);
        }
        $type = $image->type();
        $type = $type=='jpeg' ? 'jpg' : $type;
        $image_url = $this->save_path . $image_name . '.' . $type;
        $image->save($image_url);

        return str_replace(APP_ROOT, '', $this->save_path . $image_name . '.' . $type);
    }

    /**
     * 设置属性
     * @param string $field
     * @param string $value
     * @return bool
     */
    public function setParam($field = '', $value = ''){
        if(empty($field) || !isset($this->$field)){
            return false;
        }
        $this->$field = $value;
        return true;
    }

    /**
     * 设置属性
     * @param string $field
     * @return null
     */
    public function getParam($field = ''){
        if(!isset($this->$field)){
            return NULL;
        }
        return $this->$field;
    }
}