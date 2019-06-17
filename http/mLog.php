<?php
/*
 * 调用日志回调
 */

use lkeme\BiliHelper\entity\ReturnDto;
use lkeme\BiliHelper\exception\ServiceException;
use lkeme\BiliHelper\Log;
use lkeme\BiliHelper\utils\HttpCommonUtil;

require '../vendor/autoload.php';

header('Content-Type: application/json;charset=utf-8');

try {
    if(!isset($_POST['level']) || !isset($_POST['message'])){
        throw new ServiceException("缺少参数");
    }
    HttpCommonUtil::init();
    //直接调用日志的回调
    Log::callback(null, $_POST['level'], $_POST['message']);
    $returnDto = ReturnDto::success();
}catch (Exception $e){
    $returnDto = ReturnDto::exception($e);
}
echo $returnDto->toJson();
//保存时间锁到会话
HttpCommonUtil::setLockToSession();