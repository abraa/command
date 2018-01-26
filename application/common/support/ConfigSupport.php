<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/11 17:28
 * ====================================
 * File: ConfigSupport.php
 * ====================================
 */

namespace app\common\support;


use think\exception\ClassNotFoundException;

class ConfigSupport {

    /**
     * 获取数据库中的配置列表
     * @param string $name      数据库配置名称
     * @return array 配置数组
     */
    public static function getConfig($name="",$type=0){
        if(empty($name)){
            $res = cache('DB_CONFIG_DATA');
            if(empty($res)){                            //取不到尝试重新生成一次
                static::cacheConfig();
                $res = cache('DB_CONFIG_DATA');
            }
        }else{
            $res = config($name);
            if(is_null($res)){                            //取不到尝试重新生成一次
                static::cacheConfig();
                $res = config($name);
            }
            if(!empty($type)){
                $res =  isset($res[$type]) ? $res[$type] : '';
            }
        }
        return $res;
    }

    /**
     *  缓存Config   --将数据库和配置文件的config合并
     */
    public static function cacheConfig()
    {
        try {
            $db =  model('config');
        }catch (ClassNotFoundException $e){
            $db = db('config');
        }
        $data = $db->field('type,name,value,group')->select();
        $config = array();
        $group = array();
        if($data && is_array($data)){

            foreach ($data as $value) {
                if(strrpos($value['name'],".")){
                    $name = explode(".",$value['name']);                //name使用 . 的是二维参数
                    if(!isset($config[$name[0]])){
                        $config[$name[0]] = [];
                    }
                    $config[$name[0]][$name[1]] = self::parse($value['type'], $value['value']);
                    $group[$name[0]] = $name[0];
                }else{
                    $config[$value['name']] = self::parse($value['type'], $value['value']);
                }
            }
        }

        $config['CONFIG_GROUP_LIST'] = $config['CONFIG_GROUP_LIST'] + $group;

        config($config);
        cache('DB_CONFIG_DATA', $config);
    }

    /**
     * 解析value
     * @param $type
     * @param $value
     * @return array
     */
    protected static function parse($type, $value)
    {
        switch ($type) {
            case 4: //解析数组                                      1:数字,2:字符,3:文本,4:数组,5:枚举
                $value = parseConfigAttr($value);
        }
        return $value;
    }

    /**
     * 获取配置的类型
     * @param mixed $type 配置类型
     * @return string
     */
    public static function getConfigType($type=0){
        return static::getConfig('CONFIG_TYPE_LIST',$type);
    }

    /**
     * 获取配置的分组
     * @param mixed $group 配置分组
     * @return string
     */
    public static function getConfigGroup($group=0){
        return static::getConfig('CONFIG_GROUP_LIST',$group);
    }

}