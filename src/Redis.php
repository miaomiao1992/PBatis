<?php
///todo
namespace pbatis;
class Redis
{
    private static $obj = [];
    private static $redis = [];
    private static $indexs;
    private function __construct($index = 'default'){
        $conf = \json_encode(REDIS,true);
        
        self::$indexs = $index;
        self::$redis[self::$indexs] = new \Redis();
        self::$redis[self::$indexs]->connect($conf[$index]['host'], $conf[$index]['port']);
        if (self::$redis[self::$indexs]->auth($conf[$index]['password']) == false) {
            die("password:".self::$redis[self::$indexs]->getLastError());
        }
    }
    
    public static function instance($index){
        if (!isset(self::$obj[$index]) || is_null(self::$obj[$index])) {
            self::$obj[$index] = new self($index);
        }
        return self::$obj[$index];
    }
        
    /**
     * 得到一个key
     * @param $key
     */
    public function keys($str)
    {
        return self::$redis[self::$indexs]->keys($str);
    }
    
    /**
     * 设置一个key
     * @param $key
     * @param $value
     */
    public function set($key,$value)
    {
        return self::$redis[self::$indexs]->set($key,$value);
    }
    
    /**
     * 得到一个key
     * @param $key
     */
    public function get($key)
    {
        return self::$redis[self::$indexs]->get($key);
    }
    
    /**
     * 设置一个有过期时间的key
     * @param $key
     * @param $expire
     * @param $value
     */
    public function setex($key,$expire,$value)
    {
        return self::$redis[self::$indexs]->setex($key,$expire,$value);
    }
    
    /**
     * 设置一个key,如果key存在,不做任何操作.
     * @param $key
     * @param $value
     */
    public function setnx($key,$value)
    {
        return self::$redis[self::$indexs]->setnx($key,$value);
    }
    
    /**
     * 批量设置key
     * @param $arr
     */
    public function mset($arr)
    {
        return self::$redis[self::$indexs]->mset($arr);
    }
    
    
    /**
     * 得到hash表中一个字段的值
     * @param  $key
     * @param  $field
     * @return 
     */
    public function hGet($key,$field)
    {
        return self::$redis[self::$indexs]->hGet($key,$field);
    }
    
    /**
     * 为hash表设定一个字段的值
     * @param string $key 缓存key
     * @param string  $field 字段
     * @param string $value 值。
     * @return bool
     */
    public function hSet($key,$field,$value)
    {
        return self::$redis[self::$indexs]->hSet($key,$field,$value);
    }
    
    /**
     * 判断hash表中，指定field是不是存在
     * @param string $key 缓存key
     * @param string  $field 字段
     * @return bool
     */
    public function hExists($key,$field)
    {
        return self::$redis[self::$indexs]->hExists($key,$field);
    }
    
    /**
     * 删除hash表中指定字段 ,支持批量删除
     * @param string $key 缓存key
     * @param string  $field 字段
     * @return int
     */
    public function hdel($key,$field)
    {
        $fieldArr=\explode(',',$field);
        $delNum=0;
        
        foreach($fieldArr as $row)
        {
            $row=\trim($row);
            $delNum+=self::$redis[self::$indexs]->hDel($key,$row);
        }
        
        return $delNum;
    }
    
    /**
     * 返回hash表元素个数
     * @param string $key 缓存key
     * @return int|bool
     */
    public function hLen($key)
    {
        return self::$redis[self::$indexs]->hLen($key);
    }
    
    /**
     * 为hash表设定一个字段的值,如果字段存在，返回false
     * @param string $key 缓存key
     * @param string  $field 字段
     * @param string $value 值。
     * @return bool
     */
    public function hSetNx($key,$field,$value)
    {
        return self::$redis[self::$indexs]->hSetNx($key,$field,$value);
    }
    
    /**
     * 为hash表多个字段设定值。
     * @param string $key
     * @param array $value
     * @return array|bool
     */
    public function hMset($key,$value)
    {
        if(!is_array($value))
            return false;
            return self::$redis[self::$indexs]->hMset($key,$value);
    }
    
    /**
     * 为hash表多个字段设定值。
     * @param string $key
     * @param array|string $value string以','号分隔字段
     * @return array|bool
     */
    public function hMget($key,$field)
    {
        if(!\is_array($field))
            $field=\explode(',', $field);
            return self::$redis[self::$indexs]->hMget($key,$field);
    }
    
    /**
     * 为hash表设这累加，可以负数
     * @param string $key
     * @param int $field
     * @param string $value
     * @return bool
     */
    public function hIncrBy($key,$field,$value)
    {
        $value=\intval($value);
        return self::$redis[self::$indexs]->hIncrBy($key,$field,$value);
    }
    
    /**
     * 返回所有hash表的所有字段
     * @param string $key
     * @return array|bool
     */
    public function hKeys($key)
    {
        return self::$redis[self::$indexs]->hKeys($key);
    }
    
    /**
     * 返回所有hash表的字段值，为一个索引数组
     * @param string $key
     * @return array|bool
     */
    public function hVals($key)
    {
        return self::$redis[self::$indexs]->hVals($key);
    }
    
    /**
     * 返回所有hash表的字段值，为一个关联数组
     * @param string $key
     * @return array|bool
     */
    public function hGetAll($key)
    {
        return self::$redis[self::$indexs]->hGetAll($key);
    }
    
    /*********************有序集合操作*********************/
    
    /**
     * 给当前集合添加一个元素
     * 如果value已经存在，会更新order的值。
     * @param string $key
     * @param string $order 序号
     * @param string $value 值
     * @return bool
     */
    public function zAdd($key,$order,$value)
    {
        return self::$redis[self::$indexs]->zAdd($key,$order,$value);
    }
    
    /**
     * 给$value成员的order值，增加$num,可以为负数
     * @param string $key
     * @param string $num 序号
     * @param string $value 值
     * @return //返回新的order
     */
    public function zinCry($key,$num,$value)
    {
        return self::$redis[self::$indexs]->zinCry($key,$num,$value);
    }
    
    /**
     * 删除值为value的元素
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function zRem($key,$value)
    {
        return self::$redis[self::$indexs]->zRem($key,$value);
    }
    
    /**
     * 集合以order递增排列后，0表示第一个元素，-1表示最后一个元素
     * @param string $key
     * @param int $start
     * @param int $end
     * @return array|bool
     */
    public function zRange($key,$start,$end)
    {
        return self::$redis[self::$indexs]->zRange($key,$start,$end);
    }
    
    /**
     * 集合以order递减排列后，0表示第一个元素，-1表示最后一个元素
     * @param string $key
     * @param int $start
     * @param int $end
     * @return array|bool
     */
    public function zRevRange($key,$start,$end)
    {
        return self::$redis[self::$indexs]->zRevRange($key,$start,$end);
    }
    
    /**
     * 集合以order递增排列后，返回指定order之间的元素。
     * min和max可以是-inf和+inf　表示最大值，最小值
     * @param string $key
     * @param int $start
     * @param int $end
     * @package array $option 参数
     *     withscores=>true，表示数组下标为Order值，默认返回索引数组
     *     limit=>array(0,1) 表示从0开始，取一条记录。
     * @return array|bool
     */
    public function zRangeByScore($key,$start='-inf',$end="+inf",$option=array())
    {
        return self::$redis[self::$indexs]->zRangeByScore($key,$start,$end,$option);
    }
    
    /**
     * 集合以order递减排列后，返回指定order之间的元素。
     * min和max可以是-inf和+inf　表示最大值，最小值
     * @param string $key
     * @param int $start
     * @param int $end
     * @package array $option 参数
     *     withscores=>true，表示数组下标为Order值，默认返回索引数组
     *     limit=>array(0,1) 表示从0开始，取一条记录。
     * @return array|bool
     */
    public function zRevRangeByScore($key,$start='-inf',$end="+inf",$option=array())
    {
        return self::$redis[self::$indexs]->zRevRangeByScore($key,$start,$end,$option);
    }
    
    /**
     * 返回order值在start end之间的数量
     * @param unknown //$key
     * @param unknown //$start
     * @param unknown //$end
     */
    public function zCount($key,$start,$end)
    {
        return self::$redis[self::$indexs]->zCount($key,$start,$end);
    }
    
    /**
     * 返回值为value的order值
     * @param unknown //$key
     * @param unknown //$value
     */
    public function zScore($key,$value)
    {
        return self::$redis[self::$indexs]->zScore($key,$value);
    }
    
    /**
     * 返回集合以score递增加排序后，指定成员的排序号，从0开始。
     * @param unknown //$key
     * @param unknown //$value
     */
    public function zRank($key,$value)
    {
        return self::$redis[self::$indexs]->zRank($key,$value);
    }
    
    /**
     * 返回集合以score递增加排序后，指定成员的排序号，从0开始。
     * @param unknown //$key
     * @param unknown //$value
     */
    public function zRevRank($key,$value)
    {
        return self::$redis[self::$indexs]->zRevRank($key,$value);
    }
    
    /**
     * 删除集合中，score值在start end之间的元素　包括start end
     * min和max可以是-inf和+inf　表示最大值，最小值
     * @param unknown //$key
     * @param unknown //$start
     * @param unknown //$end
     * @return 删除成员的数量。
     */
    public function zRemRangeByScore($key,$start,$end)
    {
        return self::$redis[self::$indexs]->zRemRangeByScore($key,$start,$end);
    }
    
    /**
     * 返回集合元素个数。
     * @param unknown //$key
     */
    public function zCard($key)
    {
        return self::$redis[self::$indexs]->zCard($key);
    }
    /*********************队列操作命令************************/
    
    /**
     * 在队列尾部插入一个元素
     * @param unknown //$key
     * @param unknown //$value
     * 返回队列长度
     */
    public function rPush($key,$value)
    {
        return self::$redis[self::$indexs]->rPush($key,$value);
    }
    
    /**
     * 在队列尾部插入一个元素 如果key不存在，什么也不做
     * @param unknown //$key
     * @param unknown //$value
     * 返回队列长度
     */
    public function rPushx($key,$value)
    {
        return self::$redis[self::$indexs]->rPushx($key,$value);
    }
    
    /**
     * 在队列头部插入一个元素
     * @param unknown //$key
     * @param unknown //$value
     * 返回队列长度
     */
    public function lPush($key,$value)
    {
        return self::$redis[self::$indexs]->lPush($key,$value);
    }
    
    /**
     * 在队列头插入一个元素 如果key不存在，什么也不做
     * @param unknown //$key
     * @param unknown //$value
     * 返回队列长度
     */
    public function lPushx($key,$value)
    {
        return self::$redis[self::$indexs]->lPushx($key,$value);
    }
    
    /**
     * 返回队列长度
     * @param unknown //$key
     */
    public function lLen($key)
    {
        return self::$redis[self::$indexs]->lLen($key);
    }
    
    /**
     * 返回队列指定区间的元素
     * @param unknown //$key
     * @param unknown //$start
     * @param unknown //$end
     */
    public function lRange($key,$start,$end)
    {
        return self::$redis[self::$indexs]->lrange($key,$start,$end);
    }
    
    /**
     * 返回队列中指定索引的元素
     * @param unknown //$key
     * @param unknown //$index
     */
    public function lIndex($key,$index)
    {
        return self::$redis[self::$indexs]->lIndex($key,$index);
    }
    
    /**
     * 设定队列中指定index的值。
     * @param unknown //$key
     * @param unknown //$index
     * @param unknown //$value
     */
    public function lSet($key,$index,$value)
    {
        return self::$redis[self::$indexs]->lSet($key,$index,$value);
    }
    
    /**
     * 删除值为vaule的count个元素
     * PHP-REDIS扩展的数据顺序与命令的顺序不太一样，不知道是不是bug
     * count>0 从尾部开始
     *  >0　从头部开始
     *  =0　删除全部
     * @param unknown //$key
     * @param unknown //$count
     * @param unknown //$value
     */
    public function lRem($key,$count,$value)
    {
        return self::$redis[self::$indexs]->lRem($key,$value,$count);
    }
    
    /**
     * 删除并返回队列中的头元素。
     * @param unknown //$key
     */
    public function lPop($key)
    {
        return self::$redis[self::$indexs]->lPop($key);
    }
    
    /**
     * 删除并返回队列中的尾元素
     * @param unknown //$key
     */
    public function rPop($key)
    {
        return self::$redis[self::$indexs]->rPop($key);
    }
    /**
     * 删除指定的key
     * @param unknown //$key
     */
    public function del( $key1, $key2 = null, $key3 = null ) {
        return self::$redis[self::$indexs]->del( $key1, $key2 = null, $key3 = null );
    }
}