<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

use lkeme\BiliHelper\utils\HttpCommonUtil;

class File
{
    // RUN
    public static function run()
    {
    }

    // PUT CONF
    public static function writeNewEnvironmentFileWith($key, $value)
    {
        //只有非HTTP调用才写入配置文件中
        if(!HttpCommonUtil::$callByHttp) {
            $conf_file = MyIndex::getConfFile();
            file_put_contents(__DIR__ . '/../conf/' . $conf_file, preg_replace(
                '/^' . $key . '=' . getenv($key) . '/m',
                $key . '=' . $value,
                file_get_contents(__DIR__ . '/../conf/' . $conf_file)
            ));
        }
        // 写入系统变量
        putenv($key . '=' . $value);
    }
}