<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/20 14:02
 * ====================================
 * File: EmailSupport.php
 * ====================================
 */

namespace app\common\support;


class EmailSupport {

    /**
     * 使用SMTP发送邮件
     * @param $tomail
     * @param string $subject
     * @param string $body
     * @param null $attachment
     * @param null $config
     * @return bool
     */
    public  static function sendMail($tomail, $subject = '', $body = '', $attachment = null,$config=null) {
        if(empty($config)){
            $config = config('smtp_email');
        }
        $mail = new \PHPMailer();           //实例化PHPMailer对象
        $mail->IsSMTP();
        if($config['mail_auth']){
            $mail->SMTPAuth = true; // 开启SMTP认证
        }else{
            $mail->SMTPAuth = false; // 开启SMTP认证
        }
        $mail->CharSet='utf-8';
        $mail->SMTPDebug  = $config['debug'];        // 改为2可以开启调试
        $mail->Host = $config['mail_server'];      // GMAIL的SMTP
        if($config['is_ssl']){
            $mail->Encoding = "base64";
            $mail->SMTPSecure = "ssl";          // 设置连接服务器前缀
        }
        $mail->Port = $config['mail_port'];    // GMAIL的SMTP端口号
        $mail->Username = $config['mail_user']; // GMAIL用户名,必须以@gmail结尾
        $mail->Password = $config['mail_password']; // GMAIL密码
        //$mail->From ="yourphp@163.com";
        //$mail->FromName = "yourphp企业建站系统";
        $name = empty($config['mail_name']) ? config('site_name') : $config['mail_name'];
        $mail->SetFrom($config['mail_from'],$name);     //发送者邮箱
        if(!empty($config['mail_reply'])){
            $mail->AddReplyTo($config['mail_reply'],$name); //回复到这个邮箱
        }
        if(is_array($tomail)){
            foreach ($tomail as $value) {
                $mail->AddAddress($value); //可同时发多个
            }
        }else{
            $mail->AddAddress($tomail); //可同时发多个
        }
        //$mail->WordWrap = 50; // 设定 word wrap
        if($attachment){
            if(is_array($attachment)){
                foreach ($attachment as  $val) {
                    if(is_file($val)) $mail->AddAttachment($val);
                }
            }else{
                if(is_file($attachment))$mail->AddAttachment($attachment); // 附件1
            }
        }
        // $mail->AddAttachment("/var/tmp/file.tar.gz"); // 附件1
        // $mail->AddAttachment("/tmp/image.jpg", "new.jpg"); // 附件2
        $mail->IsHTML(true); // 以HTML发送
        $mail->Subject = $subject;
        $mail->Body = $body;
        // $mail->AltBody = "This is the body when user views in plain text format";		//纯文字时的Body
        return $mail->Send() ? true : $mail->ErrorInfo;
    }

}