<?php

/**
 * 列出目录下所有文件为一个数组 (文件名做id,文件路径作为path)
 * @param $basePath
 * @param $rootPath         根目录(id上目录)
 * @param bool $showAll     是否遍历所有层级
 * @return array
 */
function listFile($basePath,$rootPath,$showAll=false)
{
    if(is_dir($basePath)){
        $filelist = glob($basePath.DS."*");
        $arr = [];
        foreach($filelist as $file){
            $result = [];
            $result['text'] = str_replace($basePath.DS,"",$file);
            $result['path'] =$file;
            $result['home'] =str_replace("\\",'/',str_replace($rootPath,"",$file));
            $result['children'] =[];
            $result['id'] = str_replace('/',config('SPLIT_FILE'),$result['home']);
            if(is_dir($file)){
                $result['state'] ='closed';
                if($showAll){
                    $result['children'] =listFile($file,$rootPath,$showAll);
                }
            }
            $arr[] = $result;
        }
        return $arr;
    }else{
        return false;
    }

}


/**
 * 删除文件
 * @param $basePath
 */
function removeFile($basePath){
    if(is_file($basePath)){
        unlink($basePath);
    }else if(is_dir($basePath)){
        $filelist = glob($basePath.DS."*");
        foreach($filelist as $file){
            removeFile($file);
        }
        rmdir($basePath);
    }
}


//TODO...
