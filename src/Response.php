<?php
/**
 * 2021-03-19更新
 * 2021-06-09更新
 * 2021-7-5更新
 */

namespace pbatis;

class Response
{

    public $code;
    public $message;
    public $data;
    public $count = 0;
    private $dateTime;

    const SUCCESS=200;//成功
    const ERROR=400;//错误
    const LOG_OUT=401;//已注销或未登录
    const COOKIE_ERROR=402;//cookie无效


    public function __construct()
    {
        $this->success();
        $this->setDateTime();
    }

    /**
     * @param string $details
     * @param null $data
     * @return Response $this
     */
    public function success($details = '', $data = null): Response
    {
        $this->code = self::SUCCESS;
        $details === '' ? $this->message = '操作成功！' : $this->message = '操作成功！' . $details . '!';
        if ($data !== null) {
            $this->data = $data;
        }
        return $this;
    }

    /**
     * @param string $details
     * @param int $code
     * @param null $data
     * @return Response $this
     */
    public function error($details = '', $code=self::ERROR,$data = null)
    {
        $this->code = $code;
        $details === '' ? $this->message = '操作失败！' : $this->message = '操作失败!' . $details . '!';
        $this->data = $data;
        return $this;
    }

    public function interceptor()
    {
        $this->code = 401;
        $this->message = '登陆已失效或未登录';
        return $this->getJson();
    }

    private function setDateTime()
    {
        $this->dateTime = date("Y-m-d H:i:s");
    }

    public function setBean($bean)
    {
        foreach ($bean as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function getJson()
    {
        return json_encode($this, JSON_UNESCAPED_UNICODE);
    }

    public function set($field, $value)
    {
        $this->$field = $value;
    }

}

?>