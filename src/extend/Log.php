<?php

/**
 * 2021-03-11更新
 * 2021-03-19更新
 * 2021-03-25更新
 * 2021-04-20更新
 */

namespace pbatis\extend;

use pbatis\Date;

class Log
{

    private static $time;
    private static $user;
    private static $ip;

    private static function ip()
    {
        //strcasecmp 比较两个字符，不区分大小写。返回0，>0，<0。
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '';
        }
        return $res = preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches [0] : '';

        //  return $res;
    }

    private static function init()
    {
        self::$time = Date::getDateTime();
        defined('user') ? self::$user = \user : self::$user = 'guest';
        self::$ip = self::ip();
    }

    public static function before($controllerName)
    {
        self::init();
        //记录用户ip、浏览器、登录时间，防止ddos攻击
        self::write($controllerName . ' start ');
        return true;
    }

    public static function when($controllerName)
    {
        self::init();
        //记录各种异常
        self::write($controllerName . '：exception');

    }

    public static function after($controllerName)
    {
        self::init();
        //记录用户成功进行操作
        $user = '';
        if (isset($_SESSION) && isset($_SESSION["user"]) && self::$user === 'guest') {
            $user = $_SESSION["user"] . ' ';
        }

        self::write($user . $controllerName . ' success');
        return true;
    }


    public static function write($message = 'default', $file = 'log')
    {
        $message = is_string($message) ? $message : json_encode($message);
        self::init();
        $fileName = PBATIS_ROOT . 'log/' . $file . Date::uDate('ym', Date::getMs()) . '.txt';
        if (!file_exists(PBATIS_ROOT . 'log')) {
            \mkdir(PBATIS_ROOT . 'log', 0777, true);
        }
        if ($fp = fopen($fileName, 'a')) {
            $startTime = microtime();
            do {
                $canWrite = flock($fp, LOCK_EX);
                if (!$canWrite) usleep(round(rand(0, 100) * 1000));
            } while ((!$canWrite) && ((microtime() - $startTime) < 1000));

            if ($canWrite) {
                $dataToSave = "\r\n" . self::$time . ' ' . self::$ip . ' ' . self::$user . ' ' . $message;
                fwrite($fp, $dataToSave);
            }
            fclose($fp);
        }
    }

}

?>