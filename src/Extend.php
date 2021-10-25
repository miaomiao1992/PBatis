<?php


/**
 * Created By Yang333 *
 *  2021-02-08对非主干业务集中管理
 */

namespace pbatis;
//这里引入aop业务名字空间
use pbatis\extend\session;
use pbatis\extend\permission;
use pbatis\extend\log;

class Extend
{

    private static $before;
    public static $beforeMessage;
    private static $when;
    private static $after;

    public static function init()
    {

        self:: $before = [
            new session(),//开启登陆验证
            new log(),//记录用户ip、浏览器、登录时间，防止ddos攻击
            new permission()
        ];

        self:: $when = [
            //todo
        ];
        self:: $after = [
            new log(),//记录用户操作成功
        ];
    }

    /**AOP
     * @param string $controllerName
     * @return bool
     */
    public static function before($controllerName = 'default')
    {
        self::init();
        foreach (self::$before as $className) {
            if (!$className::before($controllerName)) {
                return false;
            }
        }
        return true;
    }

    public static function when($controllerName = 'default')
    {
        self::init();
        foreach (self::$when as $className) {
            $className::when($controllerName);
        }
    }

    public static function after($controllerName = 'default')
    {
        self::init();
        foreach (self::$after as $className) {
            $className::after($controllerName);
        }
    }


}

?>