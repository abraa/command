<?php
 /**
 * ====================================
 * thinkphp5 密码登录控制
 * ====================================
 * Author: 1002571
 * Date: 2018/1/19 14:51
 * ====================================
 * File: Passport.php
 * ====================================
 */

namespace app\people\controller;


use app\common\support\LoginSupport;
use app\people\BaseController;


class Passport extends BaseController{

    protected $allowAction = ['sendsms','sendemail','register','retrieve'];
    /**
     *  发送短信
     */
    public function sendsms(){
        $result = $this->logic->sendMobileCaptcha();
        if($result){
            $this->success(lang('send').lang('success'),$this->logic->getInfo());
        }else{
            $this->error($this->logic->getError());
        }
    }

    public function sendemail(){
        $result = $this->logic->sendEmail();
        if($result){
            $this->success(lang('send').lang('success'),$this->logic->getInfo());
        }else{
            $this->error($this->logic->getError());
        }
    }


    /**
     *  登录
     */
    public function login() {
        $this->layout(false);
        if (request()->isAjax()) {
            $result = $this->logic->login();
            if ($result) {
                $this->success($this->logic->getInfo(), url('index/index'));
            } else {
                $this->error($this->logic->getError());
            }
        } else {
            return $this->fetch();
        }
    }

    /**
     * 退出登录
     */
    public function logout() {
       LoginSupport::logout();
        $this->redirect('/');
    }

    /**
     *  注册
     */
    public function register(){
        $this->layout(false);
        if (request()->isAjax()) {
            $result = $this->logic->register();
            if ($result) {
                $this->success($this->logic->getInfo(), url('login'));
            } else {
                $this->error($this->logic->getError());
            }
        } else {
            return $this->fetch();
        }
    }


    /**
     *   找回密码
     */
    public function retrieve(){
        $this->layout(false);
        if (request()->isAjax()) {
            $result = $this->logic->retrieve();
            if ($result) {
                $this->success($this->logic->getInfo(), url('login'));
            } else {
                $this->error($this->logic->getError());
            }
        } else {
            return $this->fetch();
        }
    }

    /**
     *  修改密码
     */
    public function password(){
        if (request()->isAjax()) {
            $result = $this->logic->password();
            if ($result) {
                $this->success($this->logic->getInfo(), url('login'));
            } else {
                $this->error($this->logic->getError());
            }
        } else {
            return $this->fetch();
        }
    }

    /**
     * 验证码
     * 模版内验证码的显示
    <div>{:captcha_img()}</div>
    或者
    <div><img src="{:captcha_src()}" alt="captcha" /></div>
     */
    public function verify() {
        return captcha();
    }
}