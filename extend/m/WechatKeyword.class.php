<?php
/**
 * ====================================
 * 微信 关键字回复管理库
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-05 14:29
 * ====================================
 * File: WechatKeyword.class.php
 * ====================================
 */
namespace Common\Extend;
use Common\Extend\Wechat;

class WechatKeyword extends Wechat{

    /**
     * 查找文本关键字
     * @param string $keyword  关键字
     * @param int $event 事件
     * @return bool|mixed
     */
    static public function filterKeyword($keyword = '', $event = USER_ACT_MENU) {
        $PyWechatKeywordModel = M(null, null,'CPANEL');
        $appid = Wechat::$app_id;
        $wechat_account_id = $PyWechatKeywordModel->table('py_wechat_account')->where(array('app_id'=>$appid))->getField('id');
        if(!is_null($wechat_account_id)){
            $PyWechatKeywordModel->alias('py_wechat_keyword AS k')->join("__PY_WECHAT_KEYWORD_CONTENT__ AS kc ON k.content_id = kc.id", 'left');
            $PyWechatKeywordModel->where(array('k.type'=>$event,'k.wechat_account_id'=>$wechat_account_id, 'k.keyword'=>$keyword,'kc.locked'=>0));
            $text_content = $PyWechatKeywordModel->getField('kc.content');
            return $text_content ? $text_content : false;
        }
        return false;
    }
}
