<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

use lkeme\BiliHelper\exception\AuthException;
use lkeme\BiliHelper\exception\ServiceException;
use lkeme\BiliHelper\utils\HttpCommonUtil;

class Login
{
    public static $lock = 0;

    public static function run()
    {
        Log::info('开始启动程序...');
        if (getenv('ACCESS_TOKEN') == "") {
            Log::info('令牌载入中...');
            self::login();
        }
        // 重载配置文件
        Index::$dotenv->overload();

        Log::info('正在检查令牌合法性...');
        if (!self::info()) {
            Log::warning('令牌即将过期');
            Log::info('申请更换令牌中...');
            if (!self::refresh()) {
                Log::warning('无效令牌，正在重新申请...');
                self::login();
            }
        }
        self::$lock = time() + 3600;
    }

    public static function check()
    {
        if (self::$lock > time()) {
            return true;
        }
        self::$lock = time() + 7200;
        if (!self::info()) {
            Log::warning('令牌即将过期');
            Log::info('申请更换令牌中...');
            if (!self::refresh()) {
                Log::warning('无效令牌，正在重新申请...');
                self::login();
            }
            return false;
        }
        return true;
    }

    /**
     * 简易版令牌检查（令牌过期后自动更换，更换失败直接抛异常）
     * @return bool 是否进行撩令牌刷新
     * @throws ServiceException 异常
     */
    public static function simpleCheck(){
        if (self::$lock > time()) {
            return true;
        }
        self::$lock = time() + 7200;
        if (!self::info()) {
            Log::warning('令牌即将过期');
            Log::info('申请更换令牌中...');
            if (!self::refresh()) {
                Log::error('无效令牌，请重新登录');
                throw new ServiceException('无效令牌，请重新登录');
            }
            return false;
        }
        return true;
    }

    protected static function info()
    {
        $access_token = getenv('ACCESS_TOKEN');
        $payload = [
            'access_token' => $access_token,
        ];
        $data = Curl::get('https://passport.bilibili.com/api/v2/oauth2/info', Sign::api($payload));
        $data = json_decode($data, true);
        if (isset($data['code']) && $data['code']) {
            Log::error('检查令牌失败', ['msg' => $data['message']]);
            return false;
        }
        Log::info('令牌有效期: ' . date('Y-m-d H:i:s', $data['ts'] + $data['data']['expires_in']));
        return $data['data']['expires_in'] > 14400;
    }

    public static function refresh()
    {
        $access_token = getenv('ACCESS_TOKEN');
        $refresh_token = getenv('REFRESH_TOKEN');
        $payload = [
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
        ];
        $data = Curl::post('https://passport.bilibili.com/api/oauth2/refreshToken', Sign::api($payload));
        $data = json_decode($data, true);
        if (isset($data['code']) && $data['code']) {
            Log::error('重新生成令牌失败', ['msg' => $data['message']]);
            return false;
        }
        Log::info('令牌生成完毕!');
        $access_token = $data['data']['access_token'];
        File::writeNewEnvironmentFileWith('ACCESS_TOKEN', $access_token);
        Log::info(' > access token: ' . $access_token);
        $refresh_token = $data['data']['refresh_token'];
        File::writeNewEnvironmentFileWith('REFRESH_TOKEN', $refresh_token);
        Log::info(' > refresh token: ' . $refresh_token);
        return true;
    }

    /**
     * 登录
     * @param string $captcha 验证码
     * @param array $headers 请求头
     * @throws AuthException 异常
     */
    protected static function login($captcha = '', $headers = [])
    {
        if(HttpCommonUtil::$callByHttp){
            throw new AuthException("登录过期");
        }
        $user = getenv('APP_USER');
        $pass = getenv('APP_PASS');
        self::loginByUserPass($user, $pass, $captcha, $headers);
    }


    public static function loginByUserPass($user, $pass, $captcha = '', $headers = [])
    {
        if (empty($user) || empty($pass)) {
            Log::error('空白的帐号和口令!');
            HttpCommonUtil::myDie('空白的帐号和口令!');
        }

        // get PublicKey
        Log::info('正在载入安全模块...');
        $payload = [];
        $data = Curl::post('https://passport.bilibili.com/api/oauth2/getKey', Sign::api($payload));
        $data = json_decode($data, true);
        if (isset($data['code']) && $data['code']) {
            Log::error('公钥获取失败', ['msg' => $data['message']]);
            HttpCommonUtil::myDie('公钥获取失败:'.$data['message']);
        } else {
            Log::info('安全模块载入完毕！');
        }
        $public_key = $data['data']['key'];
        $hash = $data['data']['hash'];
        openssl_public_encrypt($hash . $pass, $crypt, $public_key);
        for ($i = 0; $i < 30; $i++) {
            // login
            Log::info('正在获取令牌...');
            $payload = [
                'subid' => 1,
                'permission' => 'ALL',
                'username' => $user,
                'password' => base64_encode($crypt),
                'captcha' => $captcha,
            ];
            $data = Curl::post('https://passport.bilibili.com/api/v2/oauth2/login', Sign::api($payload), $headers);
            $data = json_decode($data, true);
            if (isset($data['code']) && $data['code'] == -105) {
                $captcha_data = static::loginWithCaptcha();
                $captcha = $captcha_data['captcha'];
                $headers = $captcha_data['headers'];
                continue;
            }
            break;
        }
        if (isset($data['code']) && $data['code']) {
            Log::error('登录失败', ['msg' => $data['message']]);
            HttpCommonUtil::myDie('登录失败:'.$data['message']);
        }
        self::saveCookie($data);
        Log::info('令牌获取成功!');
        $access_token = $data['data']['token_info']['access_token'];
        File::writeNewEnvironmentFileWith('ACCESS_TOKEN', $access_token);
        Log::info(' > access token: ' . $access_token);
        $refresh_token = $data['data']['token_info']['refresh_token'];
        File::writeNewEnvironmentFileWith('REFRESH_TOKEN', $refresh_token);
        Log::info(' > refresh token: ' . $refresh_token);

        return;
    }


    protected static function loginWithCaptcha()
    {
        Log::info('登陆需要验证 ,启动验证码登陆!');
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'User-Agent' => 'bili-universal/8230 CFNetwork/975.0.3 Darwin/18.2.0',
            'Host' => 'passport.bilibili.com',
            'Cookie' => 'sid=blhelper'
        ];
        $data = Curl::other('https://passport.bilibili.com/captcha', null, $headers);
        $data = base64_encode($data);
        $captcha = static::ocrCaptcha($data);
        return [
            'captcha' => $captcha,
            'headers' => $headers,
        ];
    }


    private static function ocrCaptcha($captcha_img)
    {
        $payload = [
            'image' => (string)$captcha_img
        ];
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $data = Curl::other('http://115.159.205.242:19951/captcha/v1', json_encode($payload), $headers);
        $de_raw = json_decode($data, true);
        Log::info("验证码识别结果 {$de_raw['message']}");

        return $de_raw['message'];
    }

    private static function saveCookie($data)
    {
        Log::info('COOKIE获取成功!');
        //临时保存cookie
        $temp = '';
        $cookies = $data['data']['cookie_info']['cookies'];
        foreach ($cookies as $cookie) {
            $temp .= $cookie['name'] . '=' . $cookie['value'] . ';';
        }
        File::writeNewEnvironmentFileWith('COOKIE', $temp);
        Log::info(' > auth cookie: ' . $temp);
        return;
    }

}