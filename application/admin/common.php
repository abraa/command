<?php

/**
 * 列出目录下所有文件为一个数组 (文件名做key,文件路径作为value)
 * @param $basePath
 * @return array
 */
function fileList($basePath){
        $filelist = glob($basePath.DS."*");
        $arr = [];
        foreach($filelist as $file){
            $result = [];
            $result['text'] = str_replace($basePath.DS,"",$file);
            $result['path'] =$file;
            $result['home'] =str_replace("\\",'/',str_replace(PUBLIC_PATH,"",$file));
            $result['children'] =[];
            $result['id'] = str_replace('/','_',$result['home']);
            if(is_dir($file)){
                $result['state'] ='closed';
                $result['children'] =fileList($file);
            }
            $arr[] = $result;
        }
        return $arr;
    }

function fileRemove($basePath){
    if(is_file($basePath)){
        unlink($basePath);
    }else if(is_dir($basePath)){
        $filelist = glob($basePath.DS."*");
        foreach($filelist as $file){
            fileRemove($file);
        }
        rmdir($basePath);
    }
}
//TODO...
