<?php

/* 2021-02-03创建,持续改进
 * 2021-02-07改进
 * 2021-02-08改进
 * 2021-03-19改进
 */

namespace pbatis\extend;

use core\API;
use pbatis\Extend;
use pbatis\response;
use pbatis\extend\Log;

class Session
{

    public static function init($controllerName)
    {
        //todo 定义哪些控制器需要session验证，哪些是开放接口
        return API::getSessionExtend($controllerName);
    }

    public static function getUser()
    {
        return $_SESSION["user"];
    }

    /**
     * @param $controllerName
     * @return bool
     */
    public static function before($controllerName)
    {
        if (!isset($_COOKIE['PHPSESSID'])) {
            //  用户未登录状态;
            define('user', 'guest');
            define('userName', 'guest');
        } else {
            // 用户已登录;
            session_id($_COOKIE['PHPSESSID']);
            session_start();
            isset($_SESSION["user"]) ? define('user', $_SESSION["user"]) : define('user', null);
            isset($_SESSION["userName"]) ? define('userName', $_SESSION["userName"]) : define('userName', null);
        }

        // 不需要登录验证;
        if (!self::init($controllerName)) {
            return true;
        } else {

            //需要登录验证;
            if (\user === 'guest' || \user === null) {
                Extend::$beforeMessage = '未登录或登陆已失效';
                Log::write('未登录或登陆已失效');
                return false;
            }
            return true;

        }


    }

}

?>