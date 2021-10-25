<?php
/**工具类，对原生的array处理
 * 337238 yc
 * 2021-05-31创建
 * 2021-06-07更新
 * 2021-06-15更新，修复bug，正在修复
 *
 */

namespace pbatis\util;
class yArray
{
    /**利用映射关系，将单个关联数组转换为单个对象
     * @param array $map 关联数组
     * @param array $resultMapping 映射关系
     * @return \stdClass|null
     */
    public static function mapToBean($map, $resultMapping)
    {
        if ($resultMapping === [] || !$map) {
            return null;
        }
        $bean = new \stdClass();
        foreach ($map as $key => $value) {
            if (isset($resultMapping[$key])) {
                $property = $resultMapping[$key];
                $bean->$property = $value;
            } else {
                $bean->$key = $value;
            }
        }
        return $bean;
    }

    /**利用映射关系，将关联数组列表转换为对象数组
     * @param array $map 关联数组
     * @param array $resultMapping 映射关系
     * @return array|null
     */
    public static function mapToBeanList($map, $resultMapping)
    {
        if ($resultMapping === [] || !$map) {
            return null;
        }
        $beanList = [];
        for ($i = 0, $n = count($map); $i < $n; $i++) {
            $bean = self::mapToBean($map[$i], $resultMapping);
            $beanList[] = $bean;
        }
        return $beanList;

    }

    /**根据某个键值提取关联数组列表的信息
     * @param array $array 关联数组的数组
     * @param string $index 关联数组的索引
     * @return array
     */
    public static function getArrayByIndex($array, $index)
    {
        if (!isset($array) || empty($array) || !array_key_exists($index, $array[0])) {
            return [];
        }
        $result = [];
        for ($i = 0, $n = count($array); $i < $n; $i++) {
            $result[] = $array[$i][$index];
        }
        return $result;
    }

    /**关联数组列表转为索引数组列表
     * @param array $assocArrayList 关联数组列表
     * @return array $arrayList 索引数组列表
     */
    public static function assocArrayListToIndexedArrayList($assocArrayList)
    {

        $arrayList = [];
        foreach ($assocArrayList as $assocArray) {
            $array = [];
            foreach ($assocArray as $key => $value) {
                $array[] = $value;
            }
            $arrayList[] = $array;
        }
        return $arrayList;
    }

    /**对某个关联数组列表按某种方式组合
     * @param array $array
     * @param string $groupIndex
     * @param array $dataIndex
     * @return array
     */
    public static function arrayGroup($array, $groupIndex, $dataIndex = [])
    {
        $arrayGroup = [];
        $gIndex = null;
        $arr = [];
        foreach ($array as $item) {
            if ($item[$groupIndex] !== $gIndex) {
                $gIndex = $item[$groupIndex];
                $arrayGroup[] = $arr;
                $arr = [];
                $arr[$groupIndex] = $item[$groupIndex];
                for ($i = 0, $n = count($dataIndex); $i < $n; $i++) {
                    $arr['data'][$i] = [];
                }
                for ($i = 0, $n = count($dataIndex); $i < $n; $i++) {
                    $arr['data'][$i][] = $item[$dataIndex[$i]];
                }

            } else {
                for ($i = 0, $n = count($dataIndex); $i < $n; $i++) {
                    $arr['data'][$i][] = $item[$dataIndex[$i]];
                }
            }
        }
        $arrayGroup[] = $arr;
        array_splice($arrayGroup, 0, 1);
        return $arrayGroup;
    }
}
