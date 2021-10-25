<?php
/**没啥用，测试用的
 *
 */
namespace pbatis\util;
class yTable
{
    public static function render($beanList)
    {
        for ($i = 0, $n = count($beanList); $i < $n; $i++) {
            foreach ($beanList[$i] as $key => $value) {
                echo $value . '  ';
            }
            echo "\r\n";
        }
    }
}