<?php
/*
 * 百度编辑器控制器
 * @author Ocean
 * @date 2015-7-20
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;
use Common\Extend\Base\Uploader;

class UeditorController extends CpanelController{
    protected $allowAction = '*';
    private $_CONFIG = array();
    private $url;
	
	static $imageType = array('article','wechat','other');  //路径模块文件夹
	private $module_name = NULL;  //模块名称，用于替换路径{type}

    public function __construct() {
        parent::__construct();		
        header("Content-Type: text/html; charset=utf-8");
        $this->_CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(APP_ROOT . "public/static/ueditor/php/config.json")), true);
        //$imgServer = C('IMAGE_SERVER');
        //if(empty($serverId)){
         //   $serverId = array_rand($imgServer);
       // }
        //$this->url = $imgServer[$serverId];
    }

    public function index(){
		//print_r($_GET);
		$this->module_name = (isset($_GET['savePath'])&&$_GET['savePath']!='' ? strtolower($_GET['savePath']) : 'other');
		
		if(!in_array($this->module_name,self::$imageType)){
            return json_encode(array(
				'state'=> '不允许操作的模块名称'
			));
        }
		
        $action = I('get.action');
        switch ($action) {
            case 'config':
                $result =  json_encode($this->_CONFIG);
                break;

            /* 上传图片 */
            case 'uploadimage':
            /* 上传涂鸦 */
            case 'uploadscrawl':
            /* 上传视频 */
            case 'uploadvideo':
            /* 上传文件 */
            case 'uploadfile':
                /* 上传配置 */
                $result = $this->_doUpload($action);
                break;

            /* 列出图片 */
            case 'listimage':
            case 'listfile':
                $result = $this->_doList($action);
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                $result = $this->_doCatch();
                break;

            default:
                $result = json_encode(array(
                    'state'=> '请求地址出错'
                ));
                break;
        }
		
        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state'=> 'callback参数不合法'
                ));
            }
        } else {
            echo $result;
        }
    }
    
    protected function _doUpload($action){
        $base64 = "upload";
        switch (htmlspecialchars($action)) {
            case 'uploadimage':
                $config = array(
					"watermark" => isset($_GET['watermark'])&&$_GET['watermark']==1 ? true : false,  //是否增加水印
					"watermark_position" => isset($_GET['watermark_position'])&&$_GET['watermark_position']!='' ? trim($_GET['watermark_position']) : '7',  //水印位置，9宫格
					"watermark_file" => isset($_GET['watermark_file'])&&$_GET['watermark_file']!='' ? trim($_GET['watermark_file']) : 'ciji.png',  //水印文件名称
                    "pathFormat" => $this->_CONFIG['imagePathFormat'],
                    "maxSize" => $this->_CONFIG['imageMaxSize'],
                    "allowFiles" => $this->_CONFIG['imageAllowFiles']
                );
                $fieldName = $this->_CONFIG['imageFieldName'];
                break;
            case 'uploadscrawl':
                $config = array(
                    "pathFormat" => $this->_CONFIG['scrawlPathFormat'],
                    "maxSize" => $this->_CONFIG['scrawlMaxSize'],
                    "allowFiles" => $this->_CONFIG['scrawlAllowFiles'],
                    "oriName" => "scrawl.png"
                );
                $fieldName = $this->_CONFIG['scrawlFieldName'];
                $base64 = "base64";
                break;
            case 'uploadvideo':
                $config = array(
                    "pathFormat" => $this->_CONFIG['videoPathFormat'],
                    "maxSize" => $this->_CONFIG['videoMaxSize'],
                    "allowFiles" => $this->_CONFIG['videoAllowFiles']
                );
                $fieldName = $this->_CONFIG['videoFieldName'];
                break;
            case 'uploadfile':
            default:
                $config = array(
                    "pathFormat" => $this->_CONFIG['filePathFormat'],
                    "maxSize" => $this->_CONFIG['fileMaxSize'],
                    "allowFiles" => $this->_CONFIG['fileAllowFiles']
                );
                $fieldName = $this->_CONFIG['fileFieldName'];
                break;
        }
		$config['imgtype'] = $this->module_name;
        /* 生成上传实例对象并完成上传 */
        $up = new Uploader($fieldName, $config, $base64);

        /* 返回数据 */
        return json_encode($up->getFileInfo());
    }
    
    protected function _doList($action){
        switch ($action) {
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $this->_CONFIG['fileManagerAllowFiles'];
                $listSize = $this->_CONFIG['fileManagerListSize'];
                $path = $this->_CONFIG['fileManagerListPath'];
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowFiles = $this->_CONFIG['imageManagerAllowFiles'];
                $listSize = $this->_CONFIG['imageManagerListSize'];
                $path = $this->_CONFIG['imageManagerListPath'];
        }
		
		$path = str_replace("{type}", (isset($this->module_name) ? $this->module_name : ''), $path);
		
		
		if(isset($_GET['dir_list']) && $_GET['dir_list'] == 1){  //获取文件夹  Add By Lemonice
			/* 获取文件夹列表 */
			$path = $_SERVER['DOCUMENT_ROOT'] . (substr($path, 0, 1) == "/" ? "":"/") . $path;
			$list = $this->_fetchDir($path);
			
			/* 返回数据 */
			$result = json_encode(array(
				"state" => "SUCCESS",
				"is_dir" => 1,
				"list" => $list,
				"start" => 0,
				"total" => count($list)
			));
		}else{  //获取文件
			$allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
		
			/* 获取参数 */
			$size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
			$start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
			$end = $start + $size;
			
			/* 获取文件列表 */
			$path = $_SERVER['DOCUMENT_ROOT'] . (substr($path, 0, 1) == "/" ? "":"/") . $path;
			$dir_name = isset($_GET['dir_name']) ? trim($_GET['dir_name']) : '';
			if($dir_name != ''){
				$path = $path . (substr($path, -1) == "/" ? "":"/") . $dir_name . '/';
			}
			
			$files = $this->_fetchFile($path, $allowFiles);
			
			if (!count($files)) {
				return json_encode(array(
					"state" => "no match file",
					"is_dir" => 0,
					"list" => array(),
					"start" => $start,
					"total" => count($files)
				));
			}
			
			/* 获取指定范围的列表 */
			$len = count($files);
			for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
				$file_s = getimagesize(APP_ROOT.$files[$i]['url']);
				$files[$i]['width'] = isset($file_s["0"]) ? $file_s["0"] : 0;  //获取图片的宽
				$files[$i]['height'] = isset($file_s["1"]) ? $file_s["1"] : 0;  //获取图片的高
				$list[] = $files[$i];
			}
			
			/* 返回数据 */
			$result = json_encode(array(
				"state" => "SUCCESS",
				"list" => $list,
				"start" => $start,
				"total" => count($files)
			));
		}
		
		return $result;
    }
	//远程服务器  -  2016-04-06废弃
    protected function _doUrlList($action){
        switch ($action) {
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $this->_CONFIG['fileManagerAllowFiles'];
                $listSize = $this->_CONFIG['fileManagerListSize'];
                $path = $this->_CONFIG['fileManagerListPath'];
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowFiles = $this->_CONFIG['imageManagerAllowFiles'];
                $listSize = $this->_CONFIG['imageManagerListSize'];
                $path = $this->_CONFIG['imageManagerListPath'];
        }
		
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $url = $this->url . 'show?allowFiles=' . $allowFiles . '&size='.$listSize.'&path=Uploads&start='.$start;

        $opts = array('http' =>
            array(
                'timeout' => 60
            )
        );
        $context  = stream_context_create($opts);
        $files = file_get_contents($url, false, $context);
		
        if (empty($files)) {
            return json_encode(array(
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => 0
            ));
        }

        /* 获取指定范围的列表 */
        $files = json_decode($files, true);
        /* 返回数据 */
        return json_encode(array(
            "state" => "SUCCESS",
            "list" => $files['list'],
            "start" => $start,
            "total" => $files['total']
        ));
    }
	
    protected function _doCatch(){
        /* 上传配置 */
        $config = array(
			'imgtype'=>$this->module_name,
            "pathFormat" => $this->_CONFIG['catcherPathFormat'],
            "maxSize" => $this->_CONFIG['catcherMaxSize'],
            "allowFiles" => $this->_CONFIG['catcherAllowFiles'],
            "oriName" => "remote.png"
        );
        $fieldName = $this->_CONFIG['catcherFieldName'];

        /* 抓取远程图片 */
        $list = array();
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
		
        foreach ($source as $imgUrl) {
            $item = new Uploader($imgUrl, $config, "remote");
            $info = $item->getFileInfo();
            array_push($list, array(
                "state" => $info["state"],
                "url" => $info["url"],
                "size" => $info["size"],
                "title" => htmlspecialchars($info["title"]),
                "original" => htmlspecialchars($info["original"]),
                "source" => htmlspecialchars($imgUrl)
            ));
        }

        /* 返回抓取数据 */
        return json_encode(array(
            'state'=> count($list) ? 'SUCCESS':'ERROR',
            'list'=> $list
        ));
    }
	
	/**
	 * 遍历获取目录下的指定类型的文件
	 * @param $path
	 * @param array $files
	 * @return array
	 */
	private function _fetchFile($path, $allowFiles, &$files = array())
	{
		if (!is_dir($path)) return null;
		if(substr($path, strlen($path) - 1) != '/') $path .= '/';
		$handle = opendir($path);
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				$path2 = $path . $file;
				if (is_dir($path2)) {
					$this->_fetchFile($path2, $allowFiles, $files);
				} else {
					if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
						$files[] = array(
							'url'=> substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
							'mtime'=> filemtime($path2)
						);
					}
				}
			}
		}
		return $files;
	}
	
	/**
	 * 遍历获取目录下的文件夹  Add By Lemonice
	 * @param $path
	 * @param array $files
	 * @return array
	 */
	private function _fetchDir($path)
	{
		if (!is_dir($path)) return null;
		if(substr($path, strlen($path) - 1) != '/') $path .= '/';
		$handle = opendir($path);
		$dirs = array();
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				$path2 = $path . $file;
				if (is_dir($path2)) {
					$dirs[] = $file;
				}
			}
		}
		return $dirs;
	}
}