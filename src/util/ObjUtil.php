<?php

/* 2020-12-08创建,持续改进
 */

namespace pbatis\util;
class ObjUtil
{

    public static function issetField($object, $fieldName)
    {
        $filedNames = get_object_vars($object);
        return array_key_exists($fieldName, $filedNames) ? true : false;
    }

}

?>