<?php
/**
 * 2021-04-07创建
 * 2021-05-03更新
 * 2021-05-15更新
 */

namespace pbatis\extend;

class Curl
{
    private $instance;
    private $data;
    private $method;
    private $url;
    private $error;

    public function __construct()
    {
        $this->instance = curl_init();
        return $this;
    }

    /**
     * @param $opt  CurlOpt
     */
    public function setOpt($opt)
    {
        if ($opt->getUrl() === null) {
            throw new \Exception('illegal argument exception : curl url cannot be null');
        }

        $this->method = $opt->getMethod();
        $this->url = $opt->getUrl();
        curl_setopt($this->instance, CURLOPT_URL, $opt->getUrl());
        $this->setParams($opt->getParams());
        if ($this->method === 'post') {
            curl_setopt($this->instance, CURLOPT_POST, 1);
        }
        if ($opt->getCookie()) {
            curl_setopt($this->instance, CURLOPT_COOKIE, $opt->getCookie());
        }
        if ($opt->getHeader()) {
                      curl_setopt($this->instance, CURLOPT_HEADER, 1);
        }
        curl_setopt($this->instance, CURLOPT_RETURNTRANSFER, true);
        return $this;
    }

    public function setParams($params)
    {

        $parameters = '';
        foreach ($params as $key => $value) {
            $parameters = $parameters . $key . "=" . $value . "&";
        }
        $parameters = rtrim($parameters, '&');


        if ($this->method === 'post' && $parameters !== '') {
            curl_setopt($this->instance, CURLOPT_POSTFIELDS, $parameters);
        } elseif ($parameters !== '') {
            curl_setopt($this->instance, CURLOPT_URL, $this->url . "?" . $parameters);

        }

        return $this;
    }

    public function getData()
    {
        $this->data = curl_exec($this->instance);
        if (!curl_error($this->instance)) {
            $this->error = curl_error($this->instance);
        }
        return $this->data;
    }

    public function getCookie($cookieName)
    {
        if (!$this->data) {
            $this->data = curl_exec($this->instance);
        }
        preg_match('/Set-Cookie:(.*);/iU', $this->data, $str);
        if ($cookieName) {
            preg_match('/JSESSIONID=(.*)/', $str[1], $cookie);
            return $cookie[1];
        }

        return $str[1];
    }

    public function close()
    {
        curl_close($this->instance);
        $this->instance = null;
    }
}
