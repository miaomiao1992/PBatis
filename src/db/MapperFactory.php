<?php

/**
 * Created by JackYang333
 * Coporation:SAMC
 * Department:C919
 * System:Windows7/Apache2.4/PHP5.6
 * Date:2021/02/07
 * Time:16:03
 * Version--------Version
 * 2020/12/21 Version.1
 * 2021/02/08 优化通过反射生成对象，按需加载类
 * Version--------Version
 * Email:64428480@qq.com
 * QQ:644284807
 */

namespace pbatis\db;

use pbatis\extend\MyException;

class MapperFactory
{

    private static $mapperArray = [];

    /** ！注意大小写不要写错！
     * 生成[daoName=>Dao]实体数组
     * @return type array['$controllerName'=>'daoName']
     */
    private static $mapper = [
        'user' => 'User',
        'tool_list' => 'ToolList',
        'scrap_list' => 'ScrapList',
        'permission' => 'Permission',
        'tool_list_details' => 'ToolListDetails'
    ];

    //利用反射加载Dao
    private static function mapperArray($controller)
    {
        if (isset(self::$mapperArray[$controller])) {
            return self::$mapperArray[$controller];
        }
        if (!isset(self::$mapper[$controller])) {
            throw new \Exception('yBatis MapperFactory error, ' . $controller . ' initialize failure ');
            // MyException::log('MapperFactory initialize ' . $controller . ' failure');
            //    return null;
        }
        $mapperName = self::$mapper[$controller];
        try {
            $reflect = new \ReflectionClass('dao\\' . $mapperName . 'Dao');
            $arr = [];
            $arr['mapper'] = $mapperName;
            $mapper = $reflect->newInstanceArgs($arr);
        } catch (\ReflectionException $ex) {
            throw new \Exception('ReflectionError:daoName=' . $mapperName);
            $mapper = null;
        }
        return $mapper;

    }

    public static function get($controller)
    {
        $c = strtolower($controller);
        return self::mapperArray($c);
    }

}
