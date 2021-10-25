<?php


namespace pbatis\util;


class AstTag
{
    public $text = '';//文本，可能含有#{}和${}
    public $parameters = [];//绑定参数
    public $prefixAndSuffix = [];//绑定参数的前缀后缀
}