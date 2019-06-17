<?php


namespace lkeme\BiliHelper\utils;


use lkeme\BiliHelper\Daily;
use lkeme\BiliHelper\Danmu;
use lkeme\BiliHelper\entity\ReturnDto;
use lkeme\BiliHelper\exception\AuthException;
use lkeme\BiliHelper\exception\ServiceException;
use lkeme\BiliHelper\GiftHeart;
use lkeme\BiliHelper\GiftSend;
use lkeme\BiliHelper\GroupSignIn;
use lkeme\BiliHelper\Guard;
use lkeme\BiliHelper\Heart;
use lkeme\BiliHelper\Live;
use lkeme\BiliHelper\Login;
use lkeme\BiliHelper\MasterSite;
use lkeme\BiliHelper\MaterialObject;
use lkeme\BiliHelper\MyIndex;
use lkeme\BiliHelper\RaffleHandler;
use lkeme\BiliHelper\Silver;
use lkeme\BiliHelper\Silver2Coin;
use lkeme\BiliHelper\Socket;
use lkeme\BiliHelper\Task;
use lkeme\BiliHelper\Websocket;
use lkeme\BiliHelper\Winning;

class HttpCommonUtil
{
    /**
     * @var bool
     */
    public static $callByHttp = false;

    public static $saveEnvs = array('BILI_COOKIE');

    /**
     * 载入程序配置并检查登录状态
     * @throws AuthException 登录异常
     * @throws ServiceException 服务异常
     */
    public static function init(){
        self::loadConfig();
        self::checkLogin();
    }

    /**
     * 载入配置
     * @throws ServiceException 异常
     */
    public static function loadConfig(){
        //设置时区为上海
        date_default_timezone_set('Asia/Shanghai');
        //开启会话
        session_start();

        //载入程序配置
        MyIndex::loadConfigFile();
        //移除掉账户信息相关的环境变量
        self::clearUserInfoEnvironmentVariable();

        //将cookie设置到环境变量中
        if($_COOKIE != null){
            $temp = '';
            foreach ($_COOKIE as $cookie) {
                if(isset($cookie['name']) && isset($cookie['value'])){
                    $temp .= $cookie['name'] . '=' . $cookie['value'] . ';';
                }
            }
            MyIndex::setEnvironmentVariable('COOKIE', $temp);
        }

        //载入时间锁
        self::loadLockFromSession();

        //标记为通过HTTP方式调用
        self::$callByHttp = true;

    }

    /**
     * 检查登录状态
     * @throws AuthException 登录异常
     * @throws ServiceException 服务异常
     */
    public static function checkLogin(){
        //检查cookie
        if (!isset($_COOKIE['ACCESS_TOKEN']) || !isset($_COOKIE['REFRESH_TOKEN'])){
            throw new AuthException("登录失效");
        }
        //将以上TOKEN设置到环境变量中
        MyIndex::setEnvironmentVariable('ACCESS_TOKEN', $_COOKIE['ACCESS_TOKEN']);
        MyIndex::setEnvironmentVariable('REFRESH_TOKEN', $_COOKIE['REFRESH_TOKEN']);

        //检查TOKEN是否过期
        Login::simpleCheck();
    }

    /**
     * 如果是通过HTTP方式调用，则先打印返回信息，再退出
     * @param string $msg 退出信息
     */
    public static function myDie($msg='未知错误'){
        if(self::$callByHttp){
            echo ReturnDto::error($msg);
        }
        die();
    }

    /**
     * 从会话数据中获取各功能的时间锁
     */
    public static function loadLockFromSession(){
        Heart::$lock = self::getTargetLockFromSession('Heart');
        Socket::$lock = self::getTargetLockFromSession('Socket');
        Winning::$lock = self::getTargetLockFromSession('Winning');
        Live::$lock = self::getTargetLockFromSession('Live');
        Daily::$lock = self::getTargetLockFromSession('Daily');
        GiftHeart::$lock = self::getTargetLockFromSession('GiftHeart');
        Login::$lock = self::getTargetLockFromSession('Login');
        RaffleHandler::$lock = self::getTargetLockFromSession('RaffleHandler');
        Task::$lock = self::getTargetLockFromSession('Task');
        Danmu::$lock = self::getTargetLockFromSession('Danmu');
        GiftSend::$lock = self::getTargetLockFromSession('GiftSend');
        GroupSignIn::$lock = self::getTargetLockFromSession('GroupSignIn');
        MasterSite::$lock = self::getTargetLockFromSession('MasterSite');
        Silver2Coin::$lock = self::getTargetLockFromSession('Silver2Coin');
        Guard::$lock = self::getTargetLockFromSession('Guard');
        MaterialObject::$lock = self::getTargetLockFromSession('MaterialObject');
        Silver::$lock = self::getTargetLockFromSession('Silver');
        Websocket::$lock = self::getTargetLockFromSession('Websocket');

        Silver::$task = self::getTargetLockFromSession('Silver:Task');

        foreach (self::$saveEnvs as $saveEnv){
            try {
                if(isset($_SESSION[$saveEnv])) {
                    MyIndex::setEnvironmentVariable($saveEnv, $_SESSION[$saveEnv]);
                }
            } catch (ServiceException $e) {
            }
        }
    }

    /**
     * 将各功能的时间锁设置到会话数据中
     */
    public static function  setLockToSession(){
        $_SESSION['locks'] = array(
            'Heart' =>Heart::$lock,
            'Socket' =>Socket::$lock,
            'Winning' =>Winning::$lock,
            'Live' =>Live::$lock,
            'Daily' =>Daily::$lock,
            'GiftHeart' =>GiftHeart::$lock,
            'Login' =>Login::$lock,
            'RaffleHandler' =>RaffleHandler::$lock,
            'Task' =>Task::$lock,
            'Danmu' =>Danmu::$lock,
            'GiftSend' =>GiftSend::$lock,
            'GroupSignIn' =>GroupSignIn::$lock,
            'MasterSite' =>MasterSite::$lock,
            'Silver2Coin' =>Silver2Coin::$lock,
            'Guard' =>Guard::$lock,
            'MaterialObject' =>MaterialObject::$lock,
            'Silver' =>Silver::$lock,
            'Websocket' =>Websocket::$lock,
            //其它数据
            'Silver:Task' => Silver::$task
        );

        foreach (self::$saveEnvs as $saveEnv){
            $_SESSION[$saveEnv] = getenv($saveEnv);
        }
    }

    /**
     * 从会话数据中获取指定功能的时间锁
     * @param string $code 功能对应的代码
     * @return int 时间锁
     */
    private static function getTargetLockFromSession($code){
        if($code == null || !isset($_SESSION['locks']) || !isset($_SESSION['locks'][$code])){
            return 0;
        }
        $val = $_SESSION['locks'][$code];
        return $val == null? 0: $val;
    }

    /**
     * 移除掉账户信息相关的环境变量
     */
    private static function clearUserInfoEnvironmentVariable(){
        MyIndex::clearEnvironmentVariable('APP_USER');
        MyIndex::clearEnvironmentVariable('APP_PASS');
        MyIndex::clearEnvironmentVariable('ACCESS_TOKEN');
        MyIndex::clearEnvironmentVariable('REFRESH_TOKEN');
        MyIndex::clearEnvironmentVariable('COOKIE');
    }
}