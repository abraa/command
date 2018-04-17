<?php
/**
 * ====================================
 * 文章管理
 * ====================================
 * Author: lirunqing
 * Date: 2017-07-04
 * ====================================
 * File: ArticleInfoListController.class.php
 * ====================================
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;
use Common\Extend\Time;

class ArticleInfoListController extends CpanelController{
    protected $tableName = 'ArticleInfoList';
	protected $oldimg_path = 'http://useradmin.chinaskin.cn/public/upload/article/'; //后台
    //旧站文章图片缩略图目录
    protected $oldthumb_path = 'http://useradmin.chinaskin.cn/public/upload/article/thumb/';
    //文章原图片的上传目录
    protected $article_img_path = 'upload/article/';
    //文章图片缩略图的生成目录
    protected $article_img_thumb_path = 'upload/article/thumb/';

    public function __construct(){
        parent::__construct();
        $this->upload_path = APP_ROOT.'upload'; //上传基础目录
    }

    public function form() {
    	$article_id = I('article_id', 0, 'intval');
    	$articleInfoListModel = D('ArticleInfoList');

    	$info = array();
    	$info = $articleInfoListModel->where(array('article_id'=>$article_id))->find();

    	if (!empty($info)) {
            $filesArr = D('ArticleFile')->where(array('article_id'=>$article_id))->select();
            $titleImgArr = array();
            $otherFileArr = array();
            foreach ($filesArr as $key => $val) {
            	if(!file_exists('./'.$this->article_img_path.$val['file_url'])){
                    $val['original'] = $this->oldimg_path.$val['file_url'];
                    $val['thumb'] = $this->oldthumb_path.$val['file_url'];
                }else{
                    $val['original'] = '/'.$this->article_img_path.$val['file_url'];
                    $val['thumb'] = '/'.$this->article_img_thumb_path.$val['file_url'];
                }

                if ($val['type'] == 1) {
                	$titleImgArr = $val;
                }else{
                	$otherFileArr = $val;
                }
            }
            $info['title_img_url'] = $titleImgArr;
            $info['other_file_url'] = $otherFileArr;
    	}

    	$this->assign('info', $info);
        $this->display();
    }

    /**
     * 过滤内容的带域名图片链接
     * @author lirunqing 2017-7-5
     * @param  string $content
     * @return string 
     */
	public function parseImg($content = ''){
		$http = isset($_SERVER['SERVER_PROTOCOL']) ? (strstr(strtoupper($_SERVER['SERVER_PROTOCOL']),'HTTPS') ? 'https' : 'http') : 'http';
		$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

		if($content == ''){
			return $content;
		}
		$site = $http.'://'.$domain.'/';
		if(isset($_GET['ming'])){  //这个是调试用的
			echo $site."\n";
		}
		$content = str_replace('\\','',$content);
		preg_match_all('/<img[^>]*src\s*=\s*[\'"]?([^\'" >]*)/isu', $content, $src);
		
		$src = isset($src[1]) ? $src[1] : '';
		if(is_array($src) && !empty($src)){
			foreach($src as $url){
				$content = str_replace($url,str_replace($site,'/',$url),$content);
			}
		}
		return $content;
	}

    /**
     * 新增/更改文章
     * @author lirunqing 2017-7-4
     * @return json
     */
    public function save(){
    	$article_id = I('request.article_id', 0, 'intval');//文章id
        $params['art_id']       = I('request.art_id', 0, 'intval'); //分类id
        $params['title_name']   = I('request.title_name', '', 'trim'); //文章名称
        $params['is_top']     = I('request.is_top', '', 'trim'); //是否置顶
        $params['is_view']      = I('request.is_view', '', 'trim'); //是否显示
        $params['author'] = I('request.author', '', 'trim'); //文章作者
        $params['age']   = I('request.age', 0, 'intval'); //作者年龄
        $params['email']  = I('request.email', '', 'trim'); //作者邮箱
        $params['keyword']      = I('request.keyword', '', 'trim');//关键字
        $params['external_url']      = I('request.external_url', '', 'trim');//外部链接
        $params['sort_order']      = I('request.sort_order',  0, 'intval');//排序
        $params['article_introduction']      = I('request.article_introduction', '', 'trim');//文章描述

        //过滤带当前域名的图片链接中的域名部分
        $content = I('request.content');
		$params['content'] = $this->parseImg($content);

        $articleInfoListModel = D('ArticleInfoList');

        if($article_id){
            //更新
            $params['last_update'] = Time::gmTime();
            $articleInfoListModel->where(array('article_id'=>$article_id))->save($params);
        }else{
            //添加
            $params['add_time'] = Time::gmTime();
            $article_id = $articleInfoListModel->data($params)->add();
        }

        // 处理上传的图片及文件
        if(!empty($_FILES['title_img_url']['name']) || !empty($_FILES['other_file_url']['name'])){
        	$article_id = (int)$article_id;
            $this->fileUpload($_FILES, $article_id);
        }

        $this->ajaxReturn(array(
            'status'=>1,
            'info'=>'添加成功',
        ));
    }

    /**
     * 文件上传处理
     * @author lirunqing 2017-7-4
     * @param  array  $files      上传文件
     * @param  int    $article_id 文章id
     * @return [type]             [description]
     */
    public function fileUpload($files = array(),$article_id){

    	$config = array(
            'maxSize' => 2097152, //2M
            'rootPath' => $this->upload_path,
            'savePath' => '/article/'.$article_id,
            'autoSub' => true,
            'subName' => $article_id,
            'saveName' => array('makeRandName', ''),
            'exts' => array('jpg', 'gif', 'png', 'jpeg'),
        );

        $uploadClass = new \Think\Upload($config);// 实例化上传类
        $info = $uploadClass->upload();

        if(!$info){
            $this->error($uploadClass->getError());
        }

        $dataFileArr = array();
        $image = new \Think\Image();
        foreach($info as $key=>$val){
        	if($val['key'] == 'title_img_url'){//相册
        		$type = 1;
        	}else{
        		$type = 2;
        	}

        	$dataFileArr[] = array(
	            'article_id' => $article_id,
	            'file_url' => $article_id.'/'.$val['savename'],
	            'type' => $type,
	        );

	        //生成缩略图
	        $img_path = $this->upload_path.'/article/'.$article_id.'/'.$val['savename'];
	        $thumb_path = $this->upload_path.'/article/thumb/'.$article_id.'/';
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

        //相册入库
        if(!empty($dataFileArr)){
            D('ArticleFile')->data($dataFileArr)->addAll($dataFileArr);
        }
    }

    /**
     * 删除相册图片
     * @author lirunqing 2017-7-4
     * @return json
     */
    public function fileRmove(){
        $file_id = I('request.file_id', 0, 'intval');
        $article_id = I('request.article_id', 0, 'intval');
        $articleFileModel = D('ArticleFile');

        $res = $articleFileModel->where(array('file_id'=>$file_id,'article_id'=>$article_id))->delete();
        //删除日志
        if($res){
            $this->logAdmin('删除商品相册', 0, array('params'=>"img_id=$img_id and goods_id=$goods_id"));
        }
        $this->success();
    }
}