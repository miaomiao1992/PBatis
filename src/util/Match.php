<?php

/* Created by JackYang333 337238
 * Corporation:SAMC
 * Department:C919
 * System:Windows7/Apache2.4/PHP7.3
 * Date:2020/12/07
 * Time:16:03 
 * 2020/12/21 Version1.0-Beta
 * 2021/04/28 Version1.1-Beta
 * Email:64428480@qq.com
 * QQ:644284807
 * History--------History
 * 2020-11-27优化:完善textMatch
 * 2020-11-27待解决BUG：自闭合标签解析
 * 2020-12-01继续优化:1、解析#{}和${};2、if动态标签
 * 2020-12-03初步实现:1、if动态标签
 * 2020-12-04继续优化:1、if动态标签（已完成）；2、foreach标签（构思）3、待解决foreach-if标签 item
 * 2020-12-07继续优化
 * 2020-12-08继续优化：1、改成pdo和参数绑定，避免sql注入；2、待完善foreach标签（构思）
 * 2020-12-11优化:1、添加trim标签
 * 2020-12-14优化
 * 2020-12-21优化:1、使用栈，提高运行速度
 * 2021-04-27优化：1、修复解析参数BUG，目前已修复
 * 2021-04-28优化：1、添加各种注释，函数说明，终于能看懂自己写的框架了
 * 2021-04-29优化：1、textMapping代码规范化
 * 2021-04-30构思：1、建议用DOMDocument：：loadXML作为xml解析的基础准本
 * 2021-05-07修复BUG：1、${}绑定失败显示抛出异常
 * 2021-05-31改进resultMapping形式
 */


/**框架核心类，解析xml文件
 *
 */

namespace pbatis\util;

use pbatis\extend\MyException;
use pbatis\extend\Log;

class Match
{

    private $stackArray = [];//栈，临时存储AST元素，先进后出
    private $parent;
    private $parentKey = 1;
    private $xml;
    private $key = 0;

    private $root;
    private $sqlArray = [];

    function __construct($file)
    {
        $this->xml = file_get_contents($file);
        $this->root = $this->createASTElement('root', null);
        $this->parent = $this->root;
        $this->stackArray[] = $this->parent;
        $this->parseXML();
        $this->removeInvalidChars($this->root);
        $this->parseSQLArray($this->root);
    }

    public function getMatch()
    {
        return $this->root;
    }

    public function getSQLArray()
    {
        return $this->sqlArray;
    }

    /**获取结果映射配置
     * @param $mapName
     * @return \stdClass
     * @throws \Exception
     */
    public function getResultMapping($mapName)
    {
        $ele = self::findElementByAttribute("id", $mapName, $this->root);
        MyException::watch('[yBatis]cannot find resultMap by id:id=' . $mapName, $ele);
        return self::parseResultMapping($ele);
    }

    /**获取元素的id属性值
     * @param $ele
     * @return
     */
    private static function getElementId($ele)
    {
        return isset($ele->attrs['id']) ? $ele->attrs['id'] : null;
    }

    /**解析结果映射配置
     * @param $ele
     * @return \stdClass
     */
    private static function parseResultMapping($ele)
    {
        //todo
        $resultMapping=[];
        for ($i = 0,$n=count($ele->children); $i <$n ; $i++) {
            $column = $ele->children[$i]->attrs['column'];
            $resultMapping[$column] = $ele->children[$i]->attrs['property'];
        }
        return $resultMapping;



        $resultMapping = new \stdClass();
        for ($i = 0; $i < count($ele->children); $i++) {
            $column = $ele->children[$i]->attrs['column'];
            $resultMapping->$column = new \stdClass();
            $resultMapping->$column->property = $ele->children[$i]->attrs['property'];
        }
        return $resultMapping;
    }

    /**解析并获取xml文件sql对象（数组）
     * @param $ele
     * @return void
     */

    private function parseSQLArray($ele)
    {
        if ($ele->tag === 'insert' || $ele->tag === 'delete' || $ele->tag === 'update' || $ele->tag === 'select' || $ele->tag === 'outfile'||$ele->tag === 'create'||$ele->tag ==='load') {
            if ($id = self::getElementId($ele)) {
                $this->sqlArray[$id] = $ele;
            } else {
                //todo，此处应抛出异常，标签非法
            }
        }
        for ($i = 0; $i < count($ele->children); $i++) {
            $this->parseSQLArray($ele->children[$i]);
        }
    }

    /**AST语法树工具函数，去除xml文件开头n个字符
     * @param $n 去除多少个字符
     */
    private function advance($n)
    {
        $this->xml = substr($this->xml, $n);
    }

    /**解析标签<>
     * @return array
     */
    private function parseStartTag()
    {
        $ncname = '[a-zA-Z_][\\w\\-\\.]*';
        $qnameCapture = "((?:$ncname\\:)?$ncname)";
        $startTagOpen = "/^<$qnameCapture/";
        $startTagClose = '/^\s*(\/?)>/';
        $attribute = '/^\s*([^\s"\'<>\/=]+)(?:\s*(=)\s*(?:"([^"]*)"+|\'([^\']*)\'+|([^\s"\'=<>`]+)))?/';
        $start = preg_match($startTagOpen, $this->xml, $matches);
        if ($start) {
            $match = [];
            $match['tagName'] = $matches[1];
            $match['attrs'] = [];
            $this->advance(strlen($matches[0]));
            while (!($end = preg_match($startTagClose, $this->xml, $ends)) && ($attr = preg_match($attribute, $this->xml, $attrs))) {
                $this->advance(strlen($attrs[0]));
                $match['attrs'][$attrs[1]] = $attrs[3];
            }
            if ($end) {
                $match['unarySlash'] = $ends[1];
                $this->advance(strlen($ends[0]));
                return $match;
            }
        }
    }

    /**解析标签</>
     * @return mixed
     */
    private function parseEndTag()
    {
        $ncname = '[a-zA-Z_][\\w\\-\\.]*';
        $qnameCapture = "((?:$ncname\\:)?$ncname)";
        $endTag = "/^<\\/" . $qnameCapture . '[^>]*>/';
        $endTagMatch = preg_match($endTag, $this->xml, $endTagMatches);
        if ($endTagMatch) {
            $this->xml = substr($this->xml, strlen($endTagMatches[0]));
            return $endTagMatches;
        }
    }

    /**解析整个xml文件
     *
     */
    private function parseXML()
    {
        while ($this->xml) {
            $textEnd = strpos($this->xml, '<');
            if ($textEnd === 0) {
                $endTagMatch = $this->parseEndTag();
                if ($endTagMatch) {
                    \array_pop($this->stackArray);
                    $this->parent = \end($this->stackArray);
                    $this->parentKey = $this->parent->key;
                    continue;
                }
                $startTagMatch = $this->parseStartTag();
                if ($startTagMatch && $startTagMatch['unarySlash'] === '') {
                    $tag = $startTagMatch['tagName'];
                    $attrs = $startTagMatch['attrs'];
                    $element = $this->createASTElement($tag, $attrs);
                    $this->parent->children[] = $this->stackArray[] = $element;
                    $this->parent = $element;
                    $this->parentKey = $this->key;
                    continue;
                } elseif ($startTagMatch && $startTagMatch['unarySlash'] === '/') {
                    $tag = $startTagMatch['tagName'];
                    $attrs = $startTagMatch['attrs'];
                    $element = $this->createASTElement($tag, $attrs);
                    $this->parent->children[] = $element;
                    continue;
                }
                //todo bug ，可能陷入死循环，临时打了补丁
                $textEnd2 = strpos($this->xml, '</');
                $text2 = substr($this->xml, 0, $textEnd2);
                $this->parent->text = $this->parent->text . $text2;
                $this->xml = substr($this->xml, $textEnd2);
            }
            if ($textEnd >= 0) {
                $textInvalid = '/^(\r\n)*\s+(\r\n)*$/';
                $text = substr($this->xml, 0, $textEnd);
                $match = preg_match($textInvalid, $text);
                if (!$match) {
                    $this->parent->text = $this->parent->text . $text;
                }
                $this->xml = substr($this->xml, $textEnd);
            }
            if ($textEnd < 0) {
                $this->xml = '';
            }

        }
    }

    /**去除多余字符串，例如回车等等
     * @param $data
     */
    private function removeInvalidChars(&$data)
    {
        if (isset($data->text)) {
            $data->text = str_replace("\r\n", "", $data->text);
        }
        for ($i = 0; $i < count($data->children); $i++) {
            $this->removeInvalidChars($data->children[$i]);
        }

    }

    /**创建AST语法树子元素
     * @param $tag
     * @param $attrs
     * @return AstNode
     */
    private function createASTElement($tag, $attrs)
    {
        $this->key++;
        $node = new AstNode();
        $node->tag = $tag;
        $node->attrs = $attrs;
        $node->parentKey = $this->parentKey;
        $node->key = $this->key;
        return $node;
    }

    /**根据属性遍历查找某个AST元素，一般根据id属性查询
     * @param $attrName
     * @param $attr
     * @param $element
     * @return AST元素
     */
    static function findElementByAttribute($attrName, $attr, $element)
    {
        if (isset($element->attrs[$attrName]) && str_replace(' ', '', $element->attrs[$attrName]) === $attr) {
            return $element;
        }
        for ($i = 0; $i < count($element->children); $i++) {
            $ele = self::findElementByAttribute($attrName, $attr, $element->children[$i]);
            if ($ele || $i === count($element->children) - 1) {
                return $ele;
            }
        }
        return null;
    }

    /**获取AST元素某个属性值
     * @param $ele  AST元素
     * @param $attrName  属性名称
     * @return attr  属性值
     */
    private static function getAttribute($ele, $attrName)
    {
        return isset($ele->attrs[$attrName]) ? $ele->attrs[$attrName] : null;
    }

    /**解析SQL元素<if>标签，获取其中的if条件，形如eval=‘xxx!==abc’
     * @param $bean
     * @param $ele
     * @return string
     */
    private static function getEvalText($bean, $ele)
    {
        $text = self::getAttribute($ele, 'eval');
        $expIf = '/^[a-zA-Z_][\\w]*[\\s]*[!]?[<>=][=]{0,2}/';
        $expFieldName = '/^[a-zA-Z_][\\w]*[^!=<>]/';
        $str = '';
        while (strlen($text) > 0) {
            $match = preg_match($expIf, $text, $matches);
            if ($match) {
                preg_match($expFieldName, $matches[0], $matches3);
                if (ObjUtil::issetField($bean, $matches3[0])) {
                    $str = $str . "\$bean->$matches[0]";
                } else {
                    return '';
                }
                $text = substr($text, strlen($matches[0]));
                continue;
            }
            $str .= substr($text, 0, 1);
            $text = substr($text, 1);
        }

        return $str;
    }


    /**获取SQL语句某个节点（if标签等）参数绑定配置
     * @param $text
     * @return array
     * 形如，[prefixAndSuffix:[prefix:abc,Suffix:xyz]，SQLString:[key:#{xxx}，value:变量名],]
     */
    private static function textMapping($text)
    {
        $text = str_replace("\r\n", '', $text);
        $ncname = '[a-zA-Z_][\\w\\-\\.]*';
        $mValue = '/^#{\\s*[%]?\\s*' . $ncname . '\\s*[%]?\\s*}/';
        $mString = '/^\${\\s*' . $ncname . '\\s*}/';
        $mCharPrefix = '/[{][\\w\\s]*[%]/';
        $mCharSuffix = '/[\\w\\s][%][}]/';
        $mChar = '/[%]/';
        $pname = "/$ncname/";
        $textTag = '/^[\\w_\\s]*[^}#$]/';
        $matchString = [];
        $matchValue = [];
        $matchParameters = [];
        $matchPrefixAndSuffix = [];
        while ($text) {
            $textEnd1 = strpos($text, '#');
            $textEnd2 = strpos($text, '$');
            if ($textEnd1 === 0) {
                $match = preg_match($mValue, $text, $matches);
                if ($match) {
                    $match2 = preg_match($pname, $matches[0], $matches2);
                    if ($match2) {
                        $obj = [];
                        $obj['key'] = $matches[0];
                        $obj['value'] = $matches2[0];
                        $matchParameters[] = $matches2[0];
                        $matchValue[] = $obj;
                    }
                    $chars = new \stdClass();
                    $chars->prefix = null;
                    $chars->suffix = null;
                    if ($mh = preg_match($mCharPrefix, $matches[0], $mhs)) {
                        preg_match($mChar, $mhs[0], $c);
                        $chars->prefix = $c[0];
                    }
                    if ($me = preg_match($mCharSuffix, $matches[0], $mes)) {
                        preg_match($mChar, $mes[0], $c);
                        $chars->suffix = $c[0];
                    }
                    $matchPrefixAndSuffix[] = $chars;
                    $text = substr($text, strlen($matches[0]));
                } else {
                    //todo 此处可以继续优化
                    $text = substr($text, 1);
                }
                continue;
            } elseif ($textEnd2 === 0) {
                $match = preg_match($mString, $text, $matches);
                if ($match) {
                    $match2 = preg_match($pname, $matches[0], $matches2);
                    if ($match2) {
                        $matchString[$matches[0]] = $matches2[0];
                    }
                    $text = substr($text, strlen($matches[0]));
                } else {
                    //todo 此处可以继续优化
                    $text = substr($text, 1);
                }
                continue;
            }
            //todo 此处可以继续优化
            $match = preg_match($textTag, $text, $matches);
            if ($match) {
                $text = substr($text, strlen($matches[0]));
                continue;
            }
            $text = substr($text, 1);
        }
        $textMapping = [];
        $textMapping['string'] = $matchString;
        $textMapping['value'] = $matchValue;
        $textMapping['parameters'] = $matchParameters;
        $textMapping['prefixAndSuffix'] = $matchPrefixAndSuffix;
      //  Log::write($textMapping,'textMapping');
        return $textMapping;
    }

    /**根据传入的变量，对SQLString解析，将需要绑定的参数统一替换成？或者具体值
     * @param $bean
     * @param $textMap
     * @param $text
     * @return AstTag
     * 形如{"text":"limit ?,?","arr":["page","size"],"arrChars":[{"prefix":null,"suffix":null},{"prefix":null,"suffix":null}]}
     */
    private static function getAstTagOfText($bean, $textMap, $text)
    {
        //todo
        $SQLText = new AstTag();
        $text = self::getTextByString($bean, $textMap, $text);
        $SQLText->text = $text = self::getTextByValue($textMap, $text);
       // Log::write($SQLText,'sqltext');
        $SQLText->parameters = $textMap['parameters'];//需要绑定参数的参数(数组)
        $SQLText->prefixAndSuffix = $textMap['prefixAndSuffix'];//需要绑定参数的参数前缀和后缀(数组)
        return $SQLText;
    }

    /**根据传入的变量，对SQLString解析需要绑定的参数，将#{}替换成？
     * @param $SQLMatch
     * @param $text
     * @return string
     */
    private static function getTextByValue($SQLMatch, $text)
    {
        if (isset($SQLMatch) && isset($SQLMatch['value'])) {
            foreach ($SQLMatch['value'] as $array) {
                $text = str_replace($array['key'], "?", $text);
            }
        }
        return $text;
    }

    /**根据传入的变量，对SQLString解析需要绑定的参数，将${}替换成对应的值
     * @param $bean
     * @param $SQLMatch
     * @param $text
     * @return string
     */
    private static function getTextByString($bean, $SQLMatch, $text)
    {

        if (isset($SQLMatch) && isset($SQLMatch['string'])) {
            foreach ($SQLMatch['string'] as $key => $value) {
                if (!ObjUtil::issetField($bean, $value)) {
                    MyException::watch("[yBais]bind parameters by \${} error: $key");
                   // Log::write('wrong'.$value,'wrong');
                   // return '';
                } elseif ($bean->$value === null) {
                    $text = str_replace($key, 'null', $text);
                } elseif ($bean->$value === true) {
                    $text = str_replace($key, 'true', $text);
                } elseif ($bean->$value === false) {
                    $text = str_replace($key, 'false', $text);
                } else {
                    $text = str_replace($key, $bean->$value, $text);
                }
            }
        }
        return $text;
    }

    //todo

    /**对<if>标签文本进行解析，获取文本（需要绑定的参数替换成?）、前缀后缀等信息
     * @param $bean
     * @param $ele
     * @param null $byArr
     * @return AstTag
     * 形如{"text":"and column_name = ?","arr":["propertyName"],"arrChars":[{"prefix":xxx,"suffix":xxx}]}
     */
    private static function getAstTagOfIf($bean, $ele)
    {
        $tag = new AstTag();

        // by bean
        $evalStr = self::getEvalText($bean, $ele);
        settype($evalStr, 'string');
        if (eval("return $evalStr;")) {
            $textMap = self::textMapping($ele->text);
            $SQLText = self::getAstTagOfText($bean, $textMap, $ele->text);
            $tag->text = $SQLText->text;
            $tag->parameters = $SQLText->parameters;
            $tag->prefixAndSuffix = $SQLText->prefixAndSuffix;
        }

        // Log::write(json_encode($TagText));
        return $tag;
    }

    /**合并两个数组
     * @param $arr1
     * @param $arr2
     * @return array
     */
    private static function mergeArr($arr1, $arr2)
    {
        for ($i = 0; $i < count($arr2); $i++) {
            $arr1[] = $arr2[$i];
        }
        return $arr1;
    }

    /**对各种标签文本进行解析，获取文本(需要绑定的参数替换成?)、前缀后缀等信息
     * getAstTagOfIf函数的加强版
     * @param $bean
     * @param $ele
     * @return AstTag
     */
    public static function getAstTag($bean, $ele)
    {
        //todo
        if ($ele->tag === 'if') {
            //if节点
            $tag = new AstTag();
            $tagIf = self::getAstTagOfIf($bean, $ele);
            $tag->text = ' ' . $tagIf->text;//不断获取sql文本
            $tag->parameters = $tagIf->parameters;//获取绑定参数
            $tag->prefixAndSuffix = $tagIf->prefixAndSuffix;//绑定参数前缀后缀
            return $tag;
        } elseif ($ele->tag === 'trim') {
            //trim tag必有子节点
            $tag = new AstTag();
            $tag->text = $ele->attrs['prefix'];
            $text = '';
            for ($i = 0; $i < count($ele->children); $i++) {
                $astTag = self::getAstTag($bean, $ele->children[$i]);
                $text .= $astTag->text;
                $arrParameters = $astTag->parameters;
                $arrPrefixAndSuffix = $astTag->prefixAndSuffix;
                $tag->parameters = self::mergeArr($tag->parameters, $arrParameters);
                $tag->prefixAndSuffix = self::mergeArr($tag->prefixAndSuffix, $arrPrefixAndSuffix);
            }
            $text = rtrim($text, ' ');
            $text = rtrim($text, $ele->attrs['suffixOverrides']);
            $tag->text .= ' ' . $text;
            return $tag;
        } elseif ($ele->tag === 'foreach') {
            //todo，还没想好，以后可能会添加
            $str = '';
            for ($i = 0; $i < count($ele->children); $i++) {
                $str .= self::getAstTag($bean, $ele->children[$i]);
            }
            return $str;
        } elseif (isset($ele->text)) {
            //普通tag
            $tag = new AstTag();
            $textMap = self::textMapping($ele->text);
            $SQLText = self::getAstTagOfText($bean, $textMap, $ele->text);
            $tag->text = $SQLText->text;
            $tag->parameters = $SQLText->parameters;
            $tag->prefixAndSuffix = $SQLText->prefixAndSuffix;
            for ($i = 0; $i < count($ele->children); $i++) {
                $astTag = self::getAstTag($bean, $ele->children[$i]);
                $tag->text .= ' ' . $astTag->text;
                $parameters2 = $astTag->parameters;
                $prefixAndSuffix2 = $astTag->prefixAndSuffix;
                $tag->parameters = self::mergeArr($tag->parameters, $parameters2);
                $tag->prefixAndSuffix = self::mergeArr($tag->prefixAndSuffix, $prefixAndSuffix2);
            }
            return $tag;
        }
        return new AstTag();
    }
}

?>