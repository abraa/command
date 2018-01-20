<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/19 16:43
 * ====================================
 * File: Passport.php
 * ====================================
 */

namespace app\people\logic;


use app\common\support\EmailSupport;
use app\common\support\SmsSupport;
use app\people\BaseLogic;
use app\common\support\LoginSupport;
use think\captcha\Captcha;


class Passport extends BaseLogic{


    /**
     * 登陆
     */
    public function login()
    {
        $username = !empty($this->data['login_name']) ? $this->data['login_name'] : null;
        $password = !empty($this->data['login_password']) ? $this->data['login_password'] : null;
        $verify_code = !empty($this->data['verify_code']) ? $this->data['verify_code'] : null;
        if (empty($username)) {
            $this->err = lang('login_name_lost');
            return false;
        }
        if (empty($password)) {
            $this->err = lang('login_password_lost');
            return false;
        }
        if (config('verify_close')) {
            $verify = false;
            if (!empty($verify_code)) {
                $verify = captcha_check($verify_code);;
            }
            if (empty($verify)) {
                $this->err = lang('verify_code_error');
                return false;
            }
        }
        $where['user_name'] = strip_tags($username);
        $this->setModel('user');
        $userInfo = $this->dbModel->getRow($where);
        //管理不存在
        if (empty($userInfo)) {
            $this->err = lang('account_not_exists');
            return false;
        }

        //密码不正确
        if ($userInfo['password'] != password($password)) {
            $this->err = lang('password_error');
            return false;
        }
        unset($userInfo['password']);

        //已被锁定
        if ($userInfo['locked'] == 1) {
            $this->err = lang('not_allow_login');
            return false;
        }
        //获取当前用户权限
        $userInfo['menu'] = "*";
        $userInfo['login_time'] = time();
        LoginSupport::login($userInfo);             //保存登录session
        $logicData = [
            'session_key' => session_id(),
            'last_login_time' => $userInfo['now_login_time'],
            'now_login_time' => $userInfo['login_time'],
            'last_login_ip' => $userInfo['now_login_ip'],
            'now_login_ip' => request()->ip(),
        ];
        $this->dbModel->save($logicData, ['user_id' => intval($userInfo['user_id'])]);
        $this->msg = lang('login') . lang('success');
        return true;
    }

    /**
     * 注册
     * @return bool
     */
    public function register(){
        $username = !empty($this->data['login_name']) ? $this->data['login_name'] : null;
        $password = !empty($this->data['login_password']) ? $this->data['login_password'] : null;
        $verify_code = !empty($this->data['verify_code']) ? $this->data['verify_code'] : null;
        if (empty($username)) {
            $this->err = lang('login_name_lost');
            return false;
        }
        if (empty($password)) {
            $this->err = lang('login_password_lost');
            return false;
        }
        if (config('verify_close')) {
            $verify = false;
            if (!empty($verify_code)) {
                $verify = captcha_check($verify_code);;
            }
            if (empty($verify)) {
                $this->err = lang('verify_code_error');
                return false;
            }
        }
        $this->setModel('user');
        $where['user_name'] = strip_tags($username);
        $userInfo = $this->dbModel->getRow($where);
        //管理不存在
        if (empty($userInfo)) {
            $this->err = lang('account_exists');
            return false;
        }
        //TODO... 其他用户字段检查

        $data = [
            'user_name' => $username,
            'password' => password($password),
            'session_key' => session_id(),
            'last_login_time' => time(),
            'now_login_time' => time(),
            'last_login_ip' => request()->ip(),
            'now_login_ip' => request()->ip(),
        ];
        isset($this->data['real_name']) and $data['real_name'] = $this->data['real_name'];
        isset($this->data['email']) and $data['email'] = $this->data['email'];
        isset($this->data['birthday']) and $data['birthday'] = $this->data['birthday'];
        isset($this->data['phone']) and $data['phone'] = $this->data['phone'];
        isset($this->data['sex']) and $data['sex'] = $this->data['sex'];
        $this->dbModel->create($data,true);
        $this->msg = lang('register') . lang('success');
        return true;
    }
    /**
     *  发送手机验证码             验证使用图片验证码一样的 captcha_check($value,$id)
     */
    public function sendMobileCaptcha(){
        $mobile = isset($this->data['mobile']) ? $this->data['mobile'] : '';
        if(empty($mobile)){
            $this->err = lang('mobile_not_exits');
            return false;
        }
        $captcha = new Captcha();
        $id = '';                                                                    //验证码区分标识(一个页面多个验证码时需要)
        $code = [];
        $captcha->codeSet = '0123456789';
        for ($i = 0; $i < $captcha->length; $i++) {                                     //生成验证码
            $code[$i] = $captcha->codeSet[mt_rand(0, strlen($captcha->codeSet) - 1)];
        }
        $code = implode('', $code);
        $content = sprintf(lang('sms_verify_text'),$code);
        //通过反射调用private保存验证码
        $captchaClass = new \ReflectionClass(get_class($captcha));
        $method          = $captchaClass->getMethod('authcode');
        $method->setAccessible(true);
        $key = $method->invokeArgs($captchaClass, array($captcha->__get('seKey')));
        $code =$method->invokeArgs($captchaClass, array(strtoupper($code)));
        // 保存验证码
        $secode                = [];
        $secode['verify_code'] = $code; // 把校验码保存到session
        $secode['verify_time'] = time(); // 验证码创建时间
        Session::set($key . $id, $secode, '');

        //TODO... 发送短信...
        $res = SmsSupport::sendSms($mobile,$content);
        if(true == $res){
            $this->msg = lang('success');
            return true;
        }else{
            $this->err = $res;
            return false;
        }
    }


    /**
     * 修改密码
     */
    public function password(){
        $password = !empty($this->data['login_password']) ? $this->data['login_password'] : null;
        $confirm_password = !empty($this->data['confirm_password']) ? $this->data['confirm_password'] : null;
        $new_password = !empty($this->data['new_password']) ? $this->data['new_password'] : null;
        $userId = LoginSupport::getUserId();
        if (empty($userId)) {
            $this->err = lang('LOGIN_AGAIN');
            return false;
        }
        if (empty($password)||empty($new_password) || empty($confirm_password) ) {
            $this->err = lang('MISSING_PARAMS');
            return false;
        }
        if ($new_password <>$confirm_password) {
            $this->err = lang('CONFIRM_PASSWORD_ERROR');
            return false;
        }
        $this->setModel('user');
        if($this->dbModel->isUpdate(true)->save(['password'=>password($new_password)],['user_id'=>$userId])){
            $this->msg =  lang('edit') . lang('success');
            return true;
        }else{
            $this->msg = lang('edit') . lang('error');
            return false;
        }
    }


    /**
     * 找回密码             (默认使用验证码修改密码找回)
     */
    public function retrieve(){
        $mobile = !empty($this->data['mobile']) ? $this->data['mobile'] : null;
        $email = !empty($this->data['email']) ? $this->data['email'] : null;
        $confirm_password = !empty($this->data['confirm_password']) ? $this->data['confirm_password'] : null;
        $new_password = !empty($this->data['new_password']) ? $this->data['new_password'] : null;
        $verify_code = !empty($this->data['verify_code']) ? $this->data['verify_code'] : null;
        if (empty($new_password) || empty($confirm_password) ) {
            $this->err = lang('MISSING_PARAMS');
            return false;
        }
        if ($new_password <>$confirm_password) {
            $this->err = lang('CONFIRM_PASSWORD_ERROR');
            return false;
        }
        $verify = false;
        if (!empty($verify_code)) {
            $verify = captcha_check($verify_code);;
        }
        if (empty($verify)) {
            $this->err = lang('verify_code_error');
            return false;
        }
        if (!empty($mobile)) {                              //通过验证码修改密码找回
            $where = ['phone'=>$mobile];
        }elseif(!empty($email)){
            $where = ['email'=>$email];
        }else{
            //TODO...
            return false;
        }
        $this->setModel('user');
        if($this->dbModel->isUpdate(true)->save(['password'=>password($new_password)],$where)){
            $this->msg =  lang('edit') . lang('success');
            return true;
        }else{
            $this->msg = lang('edit') . lang('error');
            return false;
        }
    }

    /**
     * 发送邮件
     * @return bool
     */
    public function sendEmail(){
        $email = isset($this->data['email']) ? $this->data['email'] : '';
        if(empty($email)){
            $this->err = lang('email_not_exits');
            return false;
        }
        $captcha = new Captcha();
        $id = '';                                                                    //验证码区分标识(一个页面多个验证码时需要)
        $code = [];
        for ($i = 0; $i < $captcha->length; $i++) {                                     //生成验证码
            $code[$i] = $captcha->codeSet[mt_rand(0, strlen($captcha->codeSet) - 1)];
        }
        $code = implode('', $code);
        $content = sprintf(lang('sms_verify_text'),$code);
        //通过反射调用private保存验证码
        $captchaClass = new \ReflectionClass(get_class($captcha));
        $method          = $captchaClass->getMethod('authcode');
        $method->setAccessible(true);
        $key = $method->invokeArgs($captchaClass, array($captcha->__get('seKey')));
        $code =$method->invokeArgs($captchaClass, array(strtoupper($code)));
        // 保存验证码
        $secode                = [];
        $secode['verify_code'] = $code; // 把校验码保存到session
        $secode['verify_time'] = time(); // 验证码创建时间
        Session::set($key . $id, $secode, '');

        //TODO... 发送邮件...
        $res = EmailSupport::sendMail($tomail='', $subject = '', $body = '');
        if(true == $res){
            $this->msg = lang('success');
            return true;
        }else{
            $this->err = $res;
            return false;
        }
    }
}