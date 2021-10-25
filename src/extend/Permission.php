<?php

/**
 * 2021-02-07创建,持续改进
 */

namespace pbatis\extend;

use core\API;
use pbatis\Extend;
use pbatis\Response;
use pbatis\Request as req;
use pbatis\db\MapperFactory;

use dao\PermissionDao;

class Permission
{
    private static $arr;

    public static function init($controllerName)
    {
        //todo 定义哪些控制器需要session验证，哪些是开放接口
        self::$arr = API::getPermissionExtend($controllerName);
    }

    /**
     * @param $controllerName
     * @return bool
     */
    public static function before($controllerName)
    {
        self::init($controllerName);
        if (!self::$arr) {
            //不需要权限
            return true;
        }
        // echo json_encode(self::$arr);
        //todo 需要权限
        // $response = new Response();

        $table = self::$arr['table'];
        $permission = self::$arr['permission'];
        $permissionDao = new PermissionDao('Permission');
        $propertyName = '';
        $property = null;
        foreach (self::$arr as $key => $value) {
            $key = str_replace(' ', '', $key);
            if (strpos($key, 'params->') === 0) {
                $propertyName = $value;
                $property = req::post($value);
                break;
            } elseif ($i = strpos($key, '->')) {
                $mapName = substr($key, 0, $i);
                $propertyName = str_replace('->', '', substr($key, $i, strlen($key)));
                $propertyName = str_replace(' ', '', $propertyName);
                $myDao = MapperFactory::get($mapName);
                $bean = new \stdClass();
                $bean->id = req::post('id', 'INT');
                $flag0 = $myDao->findOneById($bean);
                if ($flag0) {
                    $property = $flag0->$propertyName;
                    break;
                } else {
                    Extend::$beforeMessage = '权限审核失败';
                    Log::write('exception of finding permission by ' . $propertyName . ' ' . $table . ' ' . $permission . ' id ' . $bean->id);
                    return false;
                    /*
                    $response->error('该数据不存在');
                    echo $response->getJson();
                      exit;
                    */
                }

            }
        }


        $bean = new \stdClass();
        $bean->user = \user;
        $bean->table = $table;
        $bean->permission = $permission;
        if ($propertyName !== '') {
            $bean->$propertyName = $property;
        }

        $bean->status = 1;

        //基本权限
        $flag1 = $permissionDao->findOne($bean);
        if ($flag1) {
            return true;
        }

        //  Log::write($bean,'bean');
        $bean->permission = 'admin';
        $flag2 = $permissionDao->findOne($bean);
        if ($flag2) {
            return true;

        }

        if ($propertyName !== '') {
            $bean->$propertyName = 'all';//额外属性
            $flag3 = $permissionDao->findOne($bean);
            if ($flag3) {
                return true;

            }
        }

        $bean->table = 'super';
        $bean->permission = $permission;
        //  Log::write($bean, 'debug');
        $flag4 = $permissionDao->findOne($bean);
        //   Log::write($flag4, 'debug');
        if ($flag4) {
            return true;

        }

        $bean->permission = 'admin';
        $flag5 = $permissionDao->findOne($bean);
        if ($flag5) {
            return true;

        }


        //  Extend::setBeforeMessage('您没有该操作权限');
        Extend::$beforeMessage = '您没有该操作权限';
        // Log::write('failure no permission ' . $table . ' ' . $permission);

        return false;

    }

}

?>