<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/9 17:40
 * ====================================
 * File: LoginSupport.php
 * ====================================
 */

namespace app\common\support;


class LoginSupport {
    protected static $loginUser = null;
    protected static $menu = null;

    /**
     * 获取登录用户信息
     * @param string $field
     * @return null|string
     */
    public static function getUserInfo($field=''){
        if(empty(static::$loginUser)){
            $key = config('LOGIN_SESSION_KEY');
            if (!is_null($key)) {
                static::$loginUser = unserialize(session($key));
            }
        }
        if (empty($field)) {
            return  static::$loginUser;
        }
        return isset(static::$loginUser[$field]) ? static::$loginUser[$field] : null;
    }

    /**
     * 保存登录后的用户数据  LogSupport::adminLog(lang('LOGIN_SUCCESS'), $adminUserInfo['user_id']);
     * @param $userInfo
     * @return bool
     */
    public static function login($userInfo){
        $key = config('LOGIN_SESSION_KEY');
        session($key, serialize($userInfo));
        return true;
    }

    /**
     *  退出登录销毁session喝用户数据
     */
    public static function logout(){
        $key = config('LOGIN_SESSION_KEY');
        session($key, null);
        static::$loginUser = null;
    }

    /**
     * 获取登录用户user_id (默认主键)
     * @return null|string
     */
    public static function getUserId(){
        return static::getUserInfo('user_id');
    }


    /**
     * 验证逻辑权限   --- 当前power是否存在用户menu中
     * @param array $power 权限数据
     * @return bool
     */
    public static function power($power)
    {
        $menu  = static::getUserInfo('menu');
        $result = false;
        if (empty($menu)) {
            return $result;
        }
        if (empty(static::$menu)) {
            static::$menu = array_reduce($menu, 'array_merge', array());
        }
        if (is_array($power)) {
            foreach ($power as $key => $item) {
                if (in_array(strtolower($item), static::$menu)) {
                    $result = true;
                    break;
                }
            }
        } else {
            $result = in_array(strtolower($power), $menu);
        }

        return $result;
    }


}