<?php


namespace lkeme\BiliHelper;


use Dotenv\Dotenv;
use lkeme\BiliHelper\exception\ServiceException;

class MyIndex
{
    public static $conf_file = null;
    /**
     * @var Dotenv
     */
    public static $dotenv = null;

    public static function getConfFile(){
        return self::$conf_file == null? 'user.conf': self::$conf_file;
    }

    // RUN
    public static function run($conf_file)
    {
        self::$conf_file = $conf_file;
        self::loadConfigFile();
        while (true) {
            if (!Login::check()) {
                self::$dotenv->overload();
            }
            Daily::run();
            MasterSite::run();
            Danmu::run();
            GiftSend::run();
            Heart::run();
            Silver::run();
            Task::run();
            Silver2Coin::run();
            GroupSignIn::run();
            Guard::run();
            Live::run();
            GiftHeart::run();
            Winning::run();
            MaterialObject::run();
            DataTreating::run();
            Websocket::run();
            usleep(0.5 * 1000000);
        }
    }

    /**
     * 加载配置文件
     * @param string $conf_file 配置文件名
     */
    public static function loadConfigFile($conf_file=null)
    {
        $conf_file = $conf_file == null? (self::$conf_file == null? 'user.conf': self::$conf_file): $conf_file;
        $file_path = __DIR__ . '/../conf/' .$conf_file;

        if (is_file($file_path) && $conf_file != 'user.conf') {
            $load_files = [
                $conf_file,
            ];
        } else {
            $default_file_path = __DIR__ . '/../conf/user.conf';
            if (!is_file($default_file_path)) {
                exit('默认加载配置文件不存在,请按照文档添加配置文件!');
            }

            $load_files = [
                'user.conf',
            ];
        }
        foreach ($load_files as $load_file) {
            self::$dotenv = new Dotenv(__DIR__ . '/../conf', $load_file);
            self::$dotenv->load();
        }
    }

    /**
     * 设置环境变量
     * @param string $name 参数名
     * @param string $value 参数值
     * @throws ServiceException 异常
     */
    public static function setEnvironmentVariable($name, $value=null){
        if($name == null){
            return;
        }
        if(self::$dotenv == null){
            throw new ServiceException("未进行配置初始化");
        }
        self::$dotenv->setEnvironmentVariable($name, $value);
    }

    /**
     * 移除指定名称的环境变量
     * @param string $name 环境变量名称
     */
    public static function clearEnvironmentVariable($name){
        self::$dotenv->clearEnvironmentVariable($name);
    }

}