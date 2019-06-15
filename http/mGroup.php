<?php
/*
 * 完成应援团签到任务
 */

use lkeme\BiliHelper\entity\ReturnDto;
use lkeme\BiliHelper\GroupSignIn;
use lkeme\BiliHelper\utils\HttpCommonUtil;

require '../vendor/autoload.php';

header('Content-Type: application/json;charset=utf-8');

try {
    HttpCommonUtil::init();
    GroupSignIn::run();
    $returnDto = ReturnDto::success();
}catch (Exception $e){
    $returnDto = ReturnDto::exception($e);
}
echo $returnDto->toJson();
//保存时间锁到会话
HttpCommonUtil::setLockToSession();