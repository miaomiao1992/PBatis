<?php


namespace pbatis\extend;


class CurlOpt
{
    private $method;
    private $url;
    private $cookie;
    private $params=[];
    private $header;

    public function __construct($method = 'get')
    {
        if ($method === 'get' || $method === 'post') {
            $this->method = $method;
            return $this;
        }
        throw new \Exception('illegal argument exception CurlOpt->method must be \'post\' or \'get\'');
    }

    /**
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url=$url;
        return $this;
    }

    public function setCookie($cookie)
    {
        $this->cookie=$cookie;
        return $this;
    }

    public function setParams($params)
    {
        $this->params=$params;
        return $this;
    }

    /**
     * @param int $header
     * @return $this
     */
    public function setHeader($header=1)
    {
        $this->header=$header;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getCookie()
    {
        return $this->cookie;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getHeader()
    {
        return $this->header;
    }
}