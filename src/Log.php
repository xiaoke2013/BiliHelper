<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

use lkeme\BiliHelper\utils\HttpCommonUtil;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Bramus\Monolog\Formatter\ColoredLineFormatter;

class Log
{
    protected static $instance;

    public static $logs = array();

    static public function getLogger()
    {
        //如果是HTTP调用，则不需要加载Logger，否则会将日志打印到返回结果中
        if (!self::$instance && !HttpCommonUtil::$callByHttp) {
            self::configureInstance();
        }
        return self::$instance;
    }

    protected static function configureInstance()
    {
        $logger = new Logger('Bilibili');
        $handler = new StreamHandler('php://stdout', getenv('APP_DEBUG') == 'true' ? Logger::DEBUG : Logger::INFO);
        $handler->setFormatter(new ColoredLineFormatter());
        $logger->pushHandler($handler);
        self::$instance = $logger;
    }

    private static function prefix()
    {
        if (getenv('APP_MULTIPLE') == 'true') {
            return '[' . (empty($t = getenv('APP_USER_IDENTITY')) ? getenv('APP_USER') : $t) . ']';
        }
        return '';
    }

    /**
     * 日志回调使用的前缀
     * @return string 前缀
     */
    private static function callbackPrefix(){
        //如果是HTTP方式调用，日志必须区分用户
        if(HttpCommonUtil::$callByHttp){
            return self::getUid();
        }
        //基于配置调用则不一定需要区分用户
        return self::prefix();
    }

    /**
     * @return string 当前用户的UID
     */
    private static function getUid(){
        return getenv('UID');
    }

    private static function writeLog($type, $message)
    {
        if (getenv('APP_WRITELOG') == 'true') {
            $path = './' . getenv("APP_WRITELOGPATH") . '/';
            if (!file_exists($path)) {
                mkdir($path);
                chmod($path, 0777);
            }
            $filename = $path . getenv('APP_USER') . ".log";
            $date = date('[Y-m-d H:i:s] ');
            $data = $date . ' Log.' . $type . ' ' . $message . PHP_EOL;
            file_put_contents($filename, $data, FILE_APPEND);
        }
        return;
    }

    public static function debug($message, array $context = [])
    {
        self::writeLog('DEBUG', $message);
        if(self::getLogger() != null) {
            self::getLogger()->addDebug($message, $context);
        }
    }

    public static function info($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::writeLog('INFO', $message);
        if(self::getLogger() != null) {
            self::getLogger()->addInfo($message, $context);
        }
        self::callback(Logger::INFO, 'INFO', $message);
    }

    public static function notice($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::writeLog('NOTICE', $message);
        if(self::getLogger() != null) {
            self::getLogger()->addNotice($message, $context);
        }
        self::callback(Logger::NOTICE, 'NOTICE', $message);
    }

    public static function warning($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::writeLog('WARNING', $message);
        if(self::getLogger() != null) {
            self::getLogger()->addWarning($message, $context);
        }
        self::callback(Logger::WARNING, 'WARNING', $message);
    }

    public static function error($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::writeLog('ERROR', $message);
        if(self::getLogger() != null) {
            self::getLogger()->addError($message, $context);
        }
        self::callback(Logger::ERROR, 'ERROR', $message);
    }

    public static function callback($levelId, $level, $message)
    {
        array_push(self::$logs, array('level' => $level, 'message' => $message));
        $callback_level = (('APP_CALLBACK_LEVEL') == '') ? (Logger::ERROR) : intval(getenv('APP_CALLBACK_LEVEL'));
        if ($levelId >= $callback_level) {
            $url = str_replace('{account}', self::callbackPrefix(), getenv('APP_CALLBACK'));
            $url = str_replace('{level}', $level, $url);
            $url = str_replace('{message}', urlencode($message), $url);
            Curl::get($url);
        }
    }
}