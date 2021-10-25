<?php


/**伪工厂模式
 * Created by JackYang333
 * QQ:644284807
 * User:Windows7/Apache2.4/PHP7.3
 * Date:2021/10/11
 * Version No.1 稳定版
 * History--------History
 * 2021/10/11 逼不得已，采用伪单例模式
 * 2021/10/12 继续完善
 * 2021/10/25 更新
 * History--------History
 */

declare(strict_types=1);

namespace pbatis\db;

use pbatis\extend\Log;

class DB
{

    /**
     * @var \PDOStatement
     */
    private $stmt;

    /**
     * @var string
     */
    private $lastSQL;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var array
     */
    private static $pdoList = [];

    /**对外提供实例对象
     * @param string $id
     * @param string $link
     * @return static
     */
    public static function getInstance(string $id = 'default', string $link = 'default'): self
    {
        if (!isset(self::$pdoList[$id]) || !self::$pdoList[$id] instanceof self || !self::$pdoList[$id]->isValid) {
            self::$pdoList[$id] = new self($link);
        }
        return self::$pdoList[$id];
    }

    /**私有构造函数
     * DB constructor.
     * @param string $link
     */
    private function __construct(string $link = 'default')
    {
        $config = \json_decode(PBATIS_DB_CONFIG, true);
        $config = \json_decode(PBATIS_DB_CONFIG, true);
        $dsn = \sprintf('mysql:host=%s;dbname=%s;charset=utf8', $config[$link]['host'], $config[$link]['dbName']);
        $option = array(
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,//fetch返回结果为关联数组
            \PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,//PDOException显式抛出
            \PDO::ATTR_STRINGIFY_FETCHES => false,//禁止fetch字符串化
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,//非缓冲模式，懒加载
        );

        return $this->pdo = new \PDO($dsn, $config[$link]['userName'], $config[$link]['password'], $option);

    }

    /**开启事务
     * @return bool
     */
    final function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**是否在事务中
     * @return bool
     */
    final function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**回滚事务
     * @return bool
     */
    final function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**提交事务
     * @return bool
     */
    final function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**关闭pdo，释放内存
     */
    private function _close(): void
    {
        $this->lastSQL = $this->pdo = $this->stmt = null;

    }

    /**关闭指定id的pdo
     * @param string $id
     */
    final  static function close(string $id = 'default'): void
    {
        if (isset(self::$pdoList[$id]) && self::$pdoList[$id] instanceof self) {
            self::$pdoList[$id]->_close();
        }
    }

    /**
     * 关闭所有pdo
     */
    final static function closeAll(): void
    {
        foreach (self::$pdoList as $id => $pdo) {
            if ($pdo instanceof self) {
                $pdo->_close();
            }
        }
        self::$pdoList = [];
    }


    /**获取错误信息
     * @return string
     */
    public function getError(): string
    {
        return json_encode($this->pdo->errorinfo());
    }

    /**预编译后执行单次insert,获取最后insert的id
     * @param $sql
     * @param $data
     * @return int
     * @throws \Exception
     */
    final function insert(string $sql, array $data): int
    {
        return true === $this->execute($sql, $data) ? (int)$this->pdo()->lastInsertId() : 0; //获取最后插入id
    }

    /**预编译后执行批量insert，获取最后insert的id
     * @param string $sql sql语句
     * @param array $dataArr 传入的数组列表
     * @return int 插入的id或者false
     * @throws \Exception
     */
    final function batchInsert(string $sql, array $dataArr = []): int
    {
        return true === $this->batchExecute($sql, $dataArr) ? (int)$this->pdo->lastInsertId() : 0;
    }

    /**预编译后执行，适合select，返回单个关联数组，懒加载
     * @param string $sql
     * @param array $data
     * @return array
     * @throws \Exception
     */
    final function fetch(string $sql, array $data = []): array
    {
        if ($this->lastSQL === $sql) {
            $rs = $this->stmt->fetch(\PDO::FETCH_ASSOC);
            return false === $rs ? [] : $rs;
        }
        $this->execute($sql, $data);
        $rs = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        return false === $rs ? [] : $rs;
    }

    /**预编译后执行，适合select，返回关联数组的数组，可能会爆内存
     * @param string $sql
     * @param array $data
     * @return array
     * @throws \Exception
     */
    final function fetchAll(string $sql, array $data = []): array
    {
        $this->execute($sql, $data);
        $rs = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
        return false === $rs ? [] : $rs;
    }

    /**获取fetch结果列名
     * @param string $sql
     * @param array $data
     * @return array
     * @throws \Exception
     */
    final function fetchColumn(string $sql, array $data = []): array
    {
        $this->execute($sql, $data);
        $rs = $this->stmt->fetch(\PDO::FETCH_NUM)[0];
        return $rs === false ? [] : $rs;
    }

    /**对sql语句预编译
     * @param string $sql sql语句
     * @throws \Exception
     */
    private function prepare(string $sql): void
    {
        $this->stmt = $this->pdo->prepare($this->lastSQL = $sql);
    }

    /**预编译后执行sql语句，适合delete和update
     * @param string $sql
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    final function execute(string $sql, array $data = []): bool
    {
        $this->prepare($sql);
        return $this->stmt->execute($data);
    }

    /**预编译后批量执行，适合delete和update
     * @param $sql
     * @param array $dataArr
     * @param int $executeNum
     * @return bool
     * @throws \Exception
     */
    final function batchExecute(string $sql, array $dataArr = [], int $executeNum = 5000): bool
    {

        //todo
        if (strpos($sql, '?') > 0 && (!is_array($dataArr) || empty($dataArr))) {
            throw new \Exception('prepare sql error');
        }
        $count = count($dataArr);
        if ($count <= 1 || !preg_match('/(\([?,\s]+\)[\s\r\n]*)$/', $sql, $groups)) {
            return $this->execute($sql, $dataArr);

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
            $this->prepare($sql);
            for ($i = 0; $i < $count; $i++) {
                foreach ($dataArr[$i] as $item) {
                    $data[] = $item;
                }

                if ($i % $executeNum === $executeNum - 1) {
                    $this->stmt->execute($data);
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
            $this->prepare($sql);
            for ($i = ((int)($count / $executeNum)) * $executeNum; $i < $count; $i++) {
                foreach ($dataArr[$i] as $item) {
                    $data[] = $item;
                }

            }
            $this->stmt->execute($data);
        }

        return true;

    }


    /**直接执行，不预编译，适合load data infile
     * @param $sql
     * @return int
     * @throws \Exception
     */
    final function exec(string $sql): int
    {
        $this->lastSQL = $sql;
        return $this->stmt = $this->pdo->exec($sql);
    }


    /**获取最后一条sql，调试专用
     * @return string
     */
    public function lastSQL(): string
    {
        return $this->lastSQL;
    }

    /**验证是否有效
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->pdo instanceof \PDO;
    }

}
