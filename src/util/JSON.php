<?php

/* 2020-11-09创建,持续改进
 * 2021-02-07改进
 * 2021-02-18改进
 */

namespace pbatis\util;

class JSON
{

    /**
     * 将json字符串转化为bean类
     * @param type $string
     * @return \stdClass
     */
    public static function toBean($string)
    {
        $bean = json_decode($string);
        if (!is_object($bean)) {
            return new \stdClass();
        } else {
            return $bean;
        }

    }

    /**
     * 将json字符串转化为关联数组
     * @param type $string
     * @return type array
     */
    public static function toArray($string)
    {

        $array = json_decode($string, true);
        if ($array === null || !is_array($array)) {
            return [];
        } else {
            return $array;
        }
    }

}

?>