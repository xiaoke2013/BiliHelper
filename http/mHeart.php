<?php
/*
 * 保持双端登录状态心跳
 */

use lkeme\BiliHelper\entity\ReturnDto;
use lkeme\BiliHelper\Heart;
use lkeme\BiliHelper\utils\HttpCommonUtil;

require '../vendor/autoload.php';

header('Content-Type: application/json;charset=utf-8');

try {
    HttpCommonUtil::init();
    Heart::run();
    $returnDto = ReturnDto::success();
}catch (Exception $e){
    $returnDto = ReturnDto::exception($e);
}
echo $returnDto->toJson();
//保存时间锁到会话
HttpCommonUtil::setLockToSession();