<?php

/**此代码永久停止更新，替换为DB
 * Created by JackYang333
 * User:Windows7/Apache2.4/PHP7.3
 * Date:2020/12/07
 * Time:16:03
 * History--------History
 * 2020/12/08 Version No.1
 * 2020/12/14 Optimize
 * 2020/12/19 Optimize
 * 2021/02/07 Optimize
 * 2021/03/22 Optimize
 * 2021/04/21 Optimize
 * 2021/05/08 Optimize，完善代码
 * 2021/05/15 完善修饰符
 * 2021/05/31 规范方法名
 * 2021/06/11 禁止fetch字符串化
 * 2021/08/21优化：1、PDOException显示抛出或者隐藏，考虑中；2、修复batchInsert-BUG
 * History--------History
 * QQ:644284807
 */

namespace pbatis\db;

use pbatis\extend\Log;

/**
 * Class PDB
 * @package core\db
 */
class PDB
{

    protected static $sth;
    private static $lastSQL;
    private static $pdo;
    private static $link = null;

    /**获取pdo，单例模式
     * @return \PDO
     * @throws \PDOException
     */
    private static function pdo()
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $config = \json_decode(PBATIS_DB_CONFIG, true);
        $dsn = \sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4;', $config[self::$link]['host'], $config[self::$link]['dbName']);
        $option = array(
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,//fetch返回结果为关联数组
            \PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,//PDOException显式抛出
            \PDO::ATTR_STRINGIFY_FETCHES => false,//禁止fetch字符串化
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,//非缓冲模式，懒加载
        );
        self::$pdo = new \PDO($dsn, $config[self::$link]['userName'], $config[self::$link]['password'], $option);

        return self::$pdo;
    }

    final static function beginTransaction($link = 'default')
    {
        self::setPDO($link);
        return self::pdo()->beginTransaction();
    }

    final static function inTransaction($link = 'default')
    {
        self::setPDO($link);
        return self::pdo()->inTransaction();
    }

    final static function rollBack($link = 'default')
    {
        self::setPDO($link);
        return self::pdo()->rollBack();
    }

    final static function commit($link = 'default')
    {
        self::setPDO($link);
        return self::pdo()->commit();
    }

    final static function closePDO($link = 'default')
    {
        self::$pdo = null;
        self::$link = null;
        self::$sth = null;
        /*
        if (self::$link === $link) {
            self::$pdo = null;
            self::$link = null;
        }*/
    }

    /**实时监控sql运行错误
     * @param $executeState
     * @param string $msg
     * @return bool
     * @throws \Exception
     */
    private static function watchException($executeState, $msg = 'execute SQL error')
    {
        if ($executeState === false || self::$pdo->errorinfo()[2]) {
            Log::write($msg . ":" . self::$lastSQL . json_encode(self::$pdo->errorinfo()), 'sql');
            throw new \PDOException("[PBatis] prepare sql error");
            return false;
        }
        return true;
    }

    public static function getError()
    {
        return json_encode(self::$pdo->errorinfo());
    }

    /**预编译后执行单次插入
     * @param string $sql sql语句
     * @param array $data 传入的数组
     * @param string $link 数据库连接配置
     * @return bool|integer  插入的id或者false
     * @throws \PDOException
     */
    final static function insert($sql, $data, $link = 'default')
    {
        //Log::write($sql,'debug');
        // Log::write($data,'debug');
        self::prepare($sql, $link);
        self::$sth->execute($data);
        return self::pdo()->lastInsertId(); //获取最后插入id
    }

    /**预编译后执行批量插入，获取最后插入的id
     * @param $sql sql语句
     * @param array $dataArr 传入的数组列表
     * @param string $link 数据库连接配置
     * @return bool|integer 插入的id或者false
     * @throws \Exception
     */
    final static function batchInsert($sql, $dataArr = [], $link = 'default')
    {
        //获取最后插入id
        return self::batchExecute($sql, $dataArr, $link) === true ? self::pdo()->lastInsertId() : false;
    }

    /**预编译后执行，适合select，返回单个关联数组
     * @param $sql
     * @param array $data
     * @param string $link
     * @return bool|null
     * @throws \Exception
     */
    final static function fetch($sql, $data = [], $link = 'default')
    {
        if (self::$lastSQL === $sql) {
           // echo '懒加载';
            $rs = self::$sth->fetch(\PDO::FETCH_ASSOC);
            return $rs === false ? null : $rs;
        }
       // echo '预编译';
        self::prepare($sql, $link);
        self::$sth->execute($data);
        $rs = self::$sth->fetch(\PDO::FETCH_ASSOC);
        return $rs === false ? null : $rs;
    }

    /**预编译后执行，适合select，返回关联数组的数组
     * @param $sql
     * @param array $data
     * @param string $link
     * @return bool|null
     * @throws \Exception
     */
    final static function fetchAll($sql, $data = [], $link = 'default')
    {
        self::prepare($sql, $link);
        self::$sth->execute($data);
        //   self::watchException(self::$sth->execute($data));
        $rs = self::$sth->fetchAll(\PDO::FETCH_ASSOC);
        return $rs === false ? null : $rs;
    }

    /* 获取一个字段，不知道有啥用*/
    final static function fetchColumn($sql, $data = [], $link = 'default')
    {
        self::prepare($sql, $link);
        self::$sth->execute($data);
        //  self::watchException(self::$sth->execute($data));
        $rs = self::$sth->fetch(\PDO::FETCH_NUM)[0];
        return $rs === false ? null : $rs;
    }

    /**对sql语句预编译
     * @param string $sql sql语句
     * @param string $link 数据库连接配置
     * @throws \Exception
     */
    private static function prepare($sql, $link = 'default')
    {
        self::setPDO($link);
        self::$lastSQL = $sql;
        self::$sth = self::pdo()->prepare($sql);
        //   self::watchException(self::$sth = self::pdo()->prepare($sql), 'prepare SQL error');
    }

    /**预编译后执行sql语句，适合delete和update
     * @param $sql
     * @param array $data
     * @param string $link
     * @return bool
     * @throws \Exception
     */
    final static function execute($sql, $data = [], $link = 'default')
    {
        self::prepare($sql, $link);
        self::$sth->execute($data);
        //  self::watchException(self::$sth->execute($data));
        return self::$sth;
    }

    /**预编译后批量执行，适合delete和update
     * @param $sql
     * @param array $dataArr
     * @param string $link
     * @param int $executeNum
     * @return bool
     * @throws \Exception
     */

    final static function batchExecute($sql, $dataArr = [], $link = 'default', $executeNum = 5000)
    {

        $count = count($dataArr);
        if ($count <= 1 || !preg_match('/(\([?,\s]+\)[\s\r\n]*)$/', $sql, $groups)) {
            self::prepare($sql, $link);
            self::$sth->execute($dataArr);
            return self::$sth;
        }
        $bindString = &$groups[1];
        $sqlPrefix = substr($sql, 0, strlen($sql) - strlen($bindString));
        $sql = $sqlPrefix;

        if ($count >= $executeNum) {
            $data = [];
            for ($i = 0; $i < $executeNum; $i++) {
                $sql .= $bindString . ',';
            }
            $sql = rtrim($sql, ',');
            self::prepare($sql, $link);
            for ($i = 0; $i < $count; $i++) {
                foreach ($dataArr[$i] as $item)
                    $data[] = $item;
                if ($i % $executeNum === $executeNum - 1) {
                    self::$sth->execute($data);
                    $data = [];
                }
            }
        }

        if ($count % $executeNum !== 0) {
            $sql = $sqlPrefix;
            $data = [];
            for ($i = 0; $i < $count % $executeNum; $i++) {
                $sql .= $bindString . ',';
            }
            $sql = rtrim($sql, ',');
            self::prepare($sql, $link);
            for ($i = ((int)($count / $executeNum)) * $executeNum; $i < $count; $i++) {
                foreach ($dataArr[$i] as $item)
                    $data[] = $item;
            }
            self::$sth->execute($data);
        }


        return self::$sth;
    }


    /**直接执行，不预编译，适合load data infile
     * @param $sql
     * @param string $link
     * @return bool
     * @throws \Exception
     */
    final static function exec($sql, $link = 'default')
    {
        self::setPDO($link);
        self::$lastSQL = $sql;
        self::$sth = self::pdo()->exec($sql);
        //  self::watchException(self::$sth = self::pdo()->exec($sql));
        return self::$sth === false ? false : true;
    }


    private static function setPDO($link)
    {
        if (self::$link !== null && self::$link !== $link) {
            self::$pdo = null;
        }
        self::$link = $link;
    }

    public static function getLastSQL()
    {
        return self::$lastSQL;
    }


}
