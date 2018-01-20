<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
if (!function_exists('logic')) {
    /**
     * 获取逻辑层类
     * @param $name
     * @param string $layer
     * @param bool $appendSuffix
     * @return \think\Controller
     */
     function logic($name, $layer = 'logic', $appendSuffix = false){
        return controller($name, $layer, $appendSuffix);
    }
}
if (!function_exists('array_map_recursive')) {
    /**
     * @param $filter
     * @param $data
     * @return array
     */
    function array_map_recursive($filter, $data)
    {
        $result = [];
        foreach ($data as $key => $val) {
            $result[$key] = is_array($val) ? array_map_recursive($filter, $val) : call_user_func($filter, $val);
        }
        return $result;
    }
}
if (!function_exists('getClientIp')) {
    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    function getClientIp($type = 0, $adv = false)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL) return $ip[$type];
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) unset($arr[$pos]);
                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
}
if (!function_exists('getDomain')) {
    /**
     * 取得当前域名地址
     * @return string
     */
    function getDomain()
    {
        /* 协议 */
        $protocol = (isSSL() ? 'https://' : 'http://');
        /* 域名或IP地址 */
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else {
            /* 端口 */
            if (isset($_SERVER['SERVER_PORT'])) {
                $port = ':' . $_SERVER['SERVER_PORT'];
                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                    $port = '';
                }
            } else {
                $port = '';
            }

            if (isset($_SERVER['SERVER_NAME'])) {
                $host = $_SERVER['SERVER_NAME'] . $port;
            } elseif (isset($_SERVER['SERVER_ADDR'])) {
                $host = $_SERVER['SERVER_sADDR'] . $port;
            }
        }

        return $protocol . (isset($host) && $host ? $host : '') . '/';
    }
}
if (!function_exists('isSSL')) {
    /**
     * 判断是否SSL协议
     * @return boolean
     */
    function isSSL()
    {
        return request()->isSsl();
    }
}
if (!function_exists('getRandom')) {
    /**
     * 产生随机字符串，不长于32位
     * @param int $length
     * @param int $mode 模式：0=字母+数字，1=数字，2=字母
     * @return string
     */
    function getRandom($length = 32, $mode = 0)
    {
        $chars = array(
            'abcdefghijklmnopqrstuvwxyz0123456789',
            '0123456789',
            'abcdefghijklmnopqrstuvwxyz',
        );
        $data = isset($chars[$mode]) ? $chars[$mode] : $chars[0];
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($data, mt_rand(0, strlen($data) - 1), 1);
        }
        return $str;
    }
}

if (!function_exists('formatTime')) {
    /**
     * 格式化时间
     * @param array $data 需要格式的数据
     * @param string $format 时间格式
     * @return array
     */
    function formatTime(&$data, $format = 'Y-m-d H:i:s')
    {
        if (empty($data)) {
            return [];
        }
        $format = empty($format) ? config('data_format') : $format;
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                formatTime($data[$key], $format);
            } else {
                if (strpos($key, 'time')) {
                    switch ($item) {
                        case 0:
                            $data[$key] = '';
                            break;
                        default:
                            $data[$key] = (is_integer($item) && $item > 0) ? date($format, $item) : $item;
                    }
                }
            }
        }
        return true;
    }
}
if (!function_exists('parseConfigAttr')) {
    /**
     * 分析枚举类型配置值 格式 a:名称1,b:名称2
     * @param $string
     * @return array|false|string[]
     */
    function parseConfigAttr($string)
    {
        $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
        if (strpos($string, ':')) {
            $value = array();
            foreach ($array as $val) {
                list($k, $v) = explode(':', $val);
                $value[$k] = $v;
            }
        } else {
            $value = $array;
        }
        return $value;
    }
}
if (!function_exists('password')) {
    /**
     * 密码处理
     * @param $password
     * @return bool|string
     */
    function password($password)
    {
        if (empty($password)) {
            return false;
        }
        return md5(md5($password) . config('CRYPT_KEY'));
    }
}
if (!function_exists('tree')) {
    /**
     * 树状图逻辑处理
     * @param array $data
     * @param string $selected
     * @param string $type
     * @return array
     */
    function tree(&$data, $selected = '', $type = '')
    {
        if (!empty($selected)) {
            $selected = strpos($selected, ',') ? explode(',', $selected) : [$selected];
            if (!empty($data) && is_array($data)) {
                foreach ($data as &$item) {
                    $item['tree_id'] = $item['id'];
                    if (in_array($item['id'], $selected)) {
                        $item['checked'] = true;
                    }
                }
            }
        }
        $data = \extend\Tree::treeArray($data);
        if (in_array($type, ['select', 'parent'])) {
            $data = [
                ['id' => 0, 'text' => lang($type . '_NODE'), 'pid' => 0, 'children' => $data]
            ];
        }
        return $data;
    }
}
if (!function_exists('isMobile')) {
    /**
     * 手机号码判断
     * @param $str
     * @return bool
     */
    function isMobile($str)
    {
        return preg_match("/^1[3|5|4|7|8]{1}[0-9]{1}[0-9]{8}$/", $str) ? true : false;
    }
}

if (!function_exists('checkPassword')) {
    /**
     * 检测密码规则
     * @param $string
     * @return bool
     */
    function checkPassword($string)
    {
        return preg_match('/^[a-zA-Z0-9]{6,10}$/', $string) ? true : false;
    }
}

if (!function_exists('keyArray')) {
    /**
     * 将数组转成key=>[[]..]
     * @param string $key 数组指定作为key的值
     * @param array $list 源数组 二维数组
     * @return array
     */
    function keyArray($key, $list)
    {
        $arr = [];
        foreach ($list as $val) {
            if (!is_array($val)) {                                    //不管是对象还是数组都转成数组处理
                if (method_exists($val, 'toArray')) {
                    $val = call_user_func([$val, 'toArray']);
                } else {
                    $val = (array)$val;
                }
            }
            if (!isset($arr[$val[$key]])) {
                $arr[$val[$key]] = [];
            }
            $arr[$val[$key]][] = $val;
        }
        return $arr;
    }
}

if (!function_exists('base64ToImage')) {
    /**
     * base64保存图片
     * @param $base
     * @param string $path
     * @param string $saveName
     * @return mixed|string|void
     */
    function base64ToImage($base , $path='' , $saveName='')
    {
        $path or $path = ROOT_PATH . 'public' . DS . 'uploads';
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base, $result)) {
            if(empty($saveName)){
                $ext = empty($result[2]) ? 'jpg' : $result[2];
                $saveName =  date('Ymd') .DS . md5(microtime(true)) . '.'. $ext;
            }
            $fileName= $path . DS . ltrim($saveName,DS);
            if (!makeDir(dirname($fileName))) {
                abort(500, '无法创建目录');
            }
            if(file_put_contents($fileName, base64_decode(str_replace($result[1], '', $base))) == false){
               abort(500, '无法创建文件');
            }
            return str_replace("\\",'/',str_replace('', ROOT_PATH . 'public',$fileName));
        }else{
            return '';
        }

    }
}



if(!function_exists('makeDir')){
    /**
     * 递归生成目录
     * 如果使用/开头则认为是使用绝对路径
     * @param $filepath
     * @return bool
     */
    function makeDir($filepath){
//    /* 路径为空返回true */
        if(empty($filepath) && 0!==$filepath && "0"!==$filepath){   // 排除0
            return true;
        }

        $reval = false;
        //1. 检查目录是否存在
        if (!file_exists($filepath)) {
            /* 如果目录不存在则尝试创建该目录 */
            @umask(0);
            //2. 检查是否有父目录
            $parentpath =  substr($filepath,0,strrpos($filepath,DS));
            //3. 调用本身检查父目录是否存在
            $res = makeDir($parentpath);
            //4. 父目录存在则生成目录
            if($res){
                if (@mkdir(rtrim($filepath, DS), 0755)) {
                    @chmod($filepath, 0755);
                    $reval = true;
                }
            }
        } else {
            /* 路径已经存在。返回该路径是不是一个目录 */
            $reval =  is_dir($filepath);
        }
        clearstatcache();
        //5. 目录存在返回 true
        return $reval;
    }
}

if(!function_exists('writeFile')) {
    /**
     * 保存文件到本地
     * @param string $filename "./uploads/qrcode/".filename.".png"
     * @param $content 二进制内容
     * @param bool|int $cover 文件存在是否覆盖并设置写入参数 FILE_USE_INCLUDE_PATH | FILE_APPEND | LOCK_EX
     * @return bool|int
     */
    function writeFile($filename, $content, $cover = true)
    {
        //1.获取图片内容
        if (empty($filename) || empty($content)) {
            return false;
        }
        //2.判断文件是否存在
        if ($cover || !is_file($filename)) {
            //判断路径是否存在并创建
            $filepath = substr($filename, 0, strrpos($filename, DS));
            if (makeDir($filepath)) {
                //3. 写入文件
                return file_put_contents($filename, $content, $cover);
            };
        }
        //4.返回结果
        return false;
    }
}

if(!function_exists('strToArray')) {
    /**
     *  将中文字符串转成数组
     * @param $str
     * @return array
     */
    function strToArray($str){
        $length = mb_strlen($str, 'utf-8');
        $array = [];
        for ($i=0; $i<$length; $i++)
            $array[] = mb_substr($str, $i, 1, 'utf-8');
        return $array;
    }
}