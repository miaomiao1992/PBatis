<?php
/**
 * AST节点
 */

namespace pbatis\util;


class AstNode
{
    public $tag;//标签
    public $attrs=null;//属性
    public $parentKey;//父节点id
    public $key;//节点id
    public $children = [];//子节点
    public $text = '';//节点文本
}