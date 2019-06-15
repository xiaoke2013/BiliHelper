<?php
/*
 * 获取用户信息
 */

use lkeme\BiliHelper\entity\ReturnDto;
use lkeme\BiliHelper\User;
use lkeme\BiliHelper\utils\HttpCommonUtil;

require '../vendor/autoload.php';

header('Content-Type: application/json;charset=utf-8');

try {
    HttpCommonUtil::init();
    $arr = User::getUserInfo();

    $returnDto = !isset($arr['data']) || $arr['data'] == null ?
        ReturnDto::error('获取用户信息失败：' . @$arr['message']) :
        ReturnDto::success($arr['data']);
}catch (Exception $e){
    $returnDto = ReturnDto::exception($e);
}
echo $returnDto->toJson();
//保存时间锁到会话
HttpCommonUtil::setLockToSession();