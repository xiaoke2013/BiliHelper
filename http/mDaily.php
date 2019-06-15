<?php
/*
 * 领取每日礼包
 */

use lkeme\BiliHelper\Daily;
use lkeme\BiliHelper\entity\ReturnDto;
use lkeme\BiliHelper\utils\HttpCommonUtil;

require '../vendor/autoload.php';

header('Content-Type: application/json;charset=utf-8');

try {
    HttpCommonUtil::init();
    Daily::run();
    $returnDto = ReturnDto::success();
}catch (Exception $e){
    $returnDto = ReturnDto::exception($e);
}
echo $returnDto->toJson();
//保存时间锁到会话
HttpCommonUtil::setLockToSession();