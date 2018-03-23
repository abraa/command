<?php
 /**
 * ====================================
 * zp
 * ====================================
 * Author: 1002571
 * Date: 2018/2/3 14:55
 * ====================================
 * File: Xml.php
 * ====================================
 */

namespace extend;


class Xml {


    public static function inputXml(){
        $xml = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] :  file_get_contents("php://input");
        return self::FromXml($xml);
    }

    /**
     * 解析xm为数组
     * @param $xml
     * @return mixed
     * @throws \Exception
     */
    public static function FromXml($xml){
        if(!$xml){
            throw new \Exception("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }


    /**
     * 获取xml编码   UTF-8
     * @param $xml
     * @return string
     */
    public static function getXmlEncode($xml) {
        $ret = preg_match ("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
        if($ret) {
            return strtoupper ( $arr[1] );
        } else {
            return "";
        }
    }
}