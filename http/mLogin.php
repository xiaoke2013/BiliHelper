<?php
/*
 * 自动领取银瓜子
 */

use lkeme\BiliHelper\entity\ReturnDto;
use lkeme\BiliHelper\exception\ServiceException;
use lkeme\BiliHelper\Login;
use lkeme\BiliHelper\utils\HttpCommonUtil;

require '../vendor/autoload.php';

header('Content-Type: application/json;charset=utf-8');

try {
    if(!isset($_POST['username']) || !isset($_POST['password']) ||
        $_POST['username'] == null || $_POST['password'] == null){
        throw new ServiceException("用户名或密码为空");
    }
    //载入配置
    HttpCommonUtil::loadConfig();
    //执行登入
    Login::loginByUserPass($_POST['username'], $_POST['password']);
    //登录成功则设置cookie, 过期时间为1个月
    setcookie("ACCESS_TOKEN", getenv('ACCESS_TOKEN'), time()+ 3600 * 24 * 30);
    setcookie("REFRESH_TOKEN", getenv('REFRESH_TOKEN'), time()+ 3600 * 24 * 30);

    $returnDto = ReturnDto::success(null, "登录成功");
}catch (Exception $e){
    $returnDto = ReturnDto::exception($e);
}
echo $returnDto->toJson();
//保存时间锁到会话
HttpCommonUtil::setLockToSession();

