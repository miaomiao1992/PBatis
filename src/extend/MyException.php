<?php

namespace pbatis\extend;

class MyException extends \Exception
{
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    public static function watch($message, $executeState = false)
    {
        if (!$executeState) {
            Log::write($message, 'ex');
            throw new \Exception($message);
        }

    }


}

?>