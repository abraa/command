<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/8/11
 * Time: 17:58
 */

namespace extend;


class QrCodeLogo {

    /**
     * 给二维码图片添加logo
     * @param $qrcode 二维码图路径
     * @param string $logo logo图路径
     */
    public static function addLogo($qrcode,$logo="./images/logo/chiaskinLog.jpg"){
        $QR = imagecreatefromstring(file_get_contents($qrcode));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度

        $logo_width = imagesx($logo);//logo图片宽度
        $logo_height = imagesy($logo);//logo图片高度
        $logo_qr_width = $QR_width / 4;
        $scale = $logo_width/$logo_qr_width;
        $logo_qr_height = $logo_height/$scale;

        $from_width = ($QR_width - $logo_qr_width) / 2;
        //重新组合图片并调整大小
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
            $logo_qr_height, $logo_width, $logo_height);
        imagepng($QR, $qrcode);   //写入原路径
    }
}