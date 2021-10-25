<?php

/**
 * Created by JackYang333
 * Corporation:SAMC
 * Department:C919
 * System:Windows7/Apache2.4/PHP5.6
 * Date:2020/12/07
 * Time:16:03
 * Version--------Version
 * 2020/12/21 Version.1(Test)
 * Version--------Version
 * Email:64428480@qq.com
 * QQ:644284807
 * History--------History
 * 2020/12/08 Optimize
 * 2020/12/09 Optimize
 * 2020/12/11 Optimize
 * 2020/12/14 Optimize
 * 2020/12/15 Optimize
 * 2020/12/21 Optimize
 * 2020/12/28 Optimize
 * 2021/01/11 Try to optimize:><
 * 2021/01/12 Optimize
 * 2021/02/08 Optimize
 * 2021/03/22 Optimize
 * 2021/05/03 Optimize
 * 2021/05/15 Optimize，修饰符protected改为final，防止子类覆盖
 * 2021/05/26 更新
 * 2021/05/31 将结果映射转换函数移到PDB类
 */

namespace pbatis\db;

use pbatis\util\AstTag;
use pbatis\util\Match;
use pbatis\util\ObjUtil;
use pbatis\util\yArray;
use pbatis\extend\MyException;


class Mapper
{

    private $mapper;
    private $match;//xml解析后的AST树
    //sql语句数组
    private $sqlArray = ['text' => '', 'resultType' => '', 'parameterType' => ''];
    //结果映射配置
    private $resultMapping;

    public function __construct($mapper)
    {

        $file = dirname(dirname(dirname(__FILE__))) . '/Xml/' . $mapper . '.xml';
        MyException::watch('yBatis Exception:cannot find xml resource by mapperName:' . $mapper, file_exists($file));
        $match = new match($file);
        //todo 这里可以存放在redis，减少服务器压力
        $this->mapper = $mapper;
        $this->match = $match->getMatch();
        $this->sqlArray = $match->getSQLArray();
        $this->resultMapping = $match->getResultMapping($mapper . "Map");
    }

    /**
     * @param $id
     * @param $bean
     * @return AstTag
     * @throws \Exception
     */
    private function getSQLById($id, $bean)
    {
        $ele = clone $this->sqlArray[$id];
        // Log::write($ele,'yBatisEle');
        MyException::watch('[yBatis]error cannot find SQL element by id:' . $id, isset($ele));
        $astTag = Match::getAstTag($bean, $this->sqlArray[$id]);
        $ele->text = $astTag->text;
        //参数绑定变量数组
        $ele->parameters = $astTag->parameters;
        //参数绑定变量的前缀后缀
        $ele->prefixAndSuffix = $astTag->prefixAndSuffix;
        return $ele;
    }

    /**批量执行插入/删除/更新
     * @param $id
     * @param array $dataArr
     * @param string $link
     * @return bool|string|null
     * @throws MyException
     */
    final function batchExecute($id, $dataArr = [], $link = 'default')
    {

        $ele = $this->getSQLById($id, $dataArr[0]);
        MyException::watch('[yBatis]error cannot get SQL by id:' . $id, isset($ele));
        $sql = $ele->text;
        $parameters = $ele->parameters;
        $data = [];
        for ($i = 0; $i < count($dataArr); $i++) {
            $bean = $dataArr[$i];
            $arr = [];
            for ($j = 0; $j < count($parameters); $j++) {
                $fieldName = $parameters[$j];
                if (ObjUtil::issetField($bean, $fieldName)) {
                    $arr[] = $bean->$fieldName;
                } else {
                    MyException::watch("[yBatis]bind parameter error:parameter=" . $arr[$j] . ' and id=' . $id . ' and mapper=' . $this->mapper);
                }
            }
            $data[] = $arr;
        }
        $rs = null;
        //todo 目前只支持insert,后续会添加update
        if ($ele->tag === 'insert') {
            $rs = PDB::batchInsert($sql, $data, $link);
        } elseif ($ele->tag === 'update' || $ele->tag === 'delete') {
            $rs = PDB::batchExecute($sql, $data, $link);
        }
        return $rs;

    }

    /**
     * @param $id sql语句的id
     * @param $bean 传入的bean参数
     * @param string $link config定义的数据库配置
     * @return array|bool|\stdClass|string|null
     * @throws MyException
     */
    final function execute($id, $bean, $link = 'default')
    {
        $ele = $this->getSQLById($id, $bean);
        MyException::watch("[yBatis]error cannot get SQL by id:" . $id, isset($ele));
        $sql = $ele->text;
        $parameters = (array)$ele->parameters;
        $prefixAndSuffix = $ele->prefixAndSuffix;
        $data = [];
        for ($i = 0; $i < count($parameters); $i++) {
            if (ObjUtil::issetField($bean, $parameters[$i])) {
                $fieldName = $parameters[$i];
                $f = $bean->$fieldName;
                if (isset($prefixAndSuffix[$i]->prefix))
                    $f = $prefixAndSuffix[$i]->prefix . $f;
                if (isset($prefixAndSuffix[$i]->suffix))
                    $f = $f . $prefixAndSuffix[$i]->suffix;
                $data[] = $f;
            } else {
                MyException::watch("[yBatis]bind parameter error:parameter=" . $parameters[$i] . ' and id=' . $id . ' and mapper=' . $this->mapper);
            }
        }

        $rs = null;

        if ($ele->tag === 'insert' && $ele->attrs['parameterType'] === 'bean') {
            $rs = PDB::insert($sql, $data, $link);
        } elseif ($ele->tag === 'insert') {
            $rs = PDB::insert($sql, $data, $link); //todo
        } else if ($ele->tag === 'delete' || $ele->tag === 'update') {
            $rs = PDB::execute($sql, $data, $link);
        } else if ($ele->tag === 'select') {
            if ($ele->attrs['resultType'] === 'bean') {
                $rs = PDB::fetch($sql, $data, $link);
                $rs = yArray::mapToBean($rs, $this->resultMapping);
            } else if ($ele->attrs['resultType'] === 'list<bean>') {
                $rs = PDB::fetchAll($sql, $data, $link);
                $rs =  yArray::mapToBeanList($rs, $this->resultMapping);
            } else if ($ele->attrs['resultType'] === 'int') {
                //todo 还没想好
                $rs = PDB::fetch($sql, $data, $link);
                foreach ($rs as $key => $value) {
                    $rs2 = $value;
                }
                $rs = $rs2;
            } else {
                $rs = PDB::fetchColumn($sql, $data, $link);
            }
        } elseif ($ele->tag === 'outfile') {
            $rs = PDB::execute($sql, $data, $link);
        } elseif ($ele->tag === 'create') {
            $rs = PDB::execute($sql, $data, $link);
        } elseif ($ele->tag === 'load') {
            //这里不能预编译,所以不要传$data了！
            $rs = PDB::exec($sql, $link);
        }

        return $rs;
    }




}
