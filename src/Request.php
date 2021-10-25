<?php
/**
 * 2021-03-19更新
 * 2021-07-05更新
 * 2021-09-23更新
 *
 */

namespace pbatis;

use pbatis\util\JSON;

class Request
{

    private static $map = [
        'int' => 'integer',
        'integer' => 'integer',
        'float' => 'float',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'str' => 'string',
        'string' => 'string',
    ];

    /**
     * @param $type
     * @return array
     */
    private static function requestType($type): array
    {
        if ($type === 'get') {
            return $_GET;
        } elseif ($type === 'post') {
            return $_POST;
        }

        return $_COOKIE;
    }

    private static function request($var, $key, $type = 'default', $exp = null)
    {

        $type = strtolower($type);
        if (!isset($var[$key]) && $type !== 'bean') {
            return null;
        } elseif (!isset($var[$key]) && $type === 'bean') {
            return new \stdClass();
        } elseif (isset(self::$map[$type])) {
            settype($var[$key], self::$map[$type]);
            return $var[$key];
        } elseif ($type === 'bean') {
            return JSON::toBean($var[$key]);
        } elseif ($type === 'array') {
            return JSON::toArray($var[$key]);
        } else {
            return $var[$key];
        }
    }

    /** post请求
     * @param $key
     * @param string $type
     * @param null $exp
     * @return array|util\type|mixed|\stdClass|null
     */
    public static function post($key, $type = 'default', $exp = null)
    {
        return self::request(self::requestType('post'), $key, $type, $exp);
    }

    /**get 请求
     * @param $key
     * @param string $type
     * @param null $exp
     * @return array|util\type|mixed|\stdClass|null
     */
    public static function get($key, $type = 'default', $exp = null)
    {

        return self::request(self::requestType('get'), $key, $type, $exp);
    }


    /**
     * @param $key
     * @param string $type
     * @param null $exp
     * @return array|util\type|mixed|\stdClass|null
     */
    public static function cookie($key, $type = 'default', $exp = null)
    {

        return self::request(self::requestType('cookie'), $key, $type = 'default', $exp);

    }

}
