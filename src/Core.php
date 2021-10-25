<?php
/**
 * Created by JackYang333 337238
 * @created 2020/12/07 16:03
 * @update 2021/10/25 优化
 */

namespace pbatis;

use pbatis\extend\Log;

class Core
{

    protected $config = [];
    private static $core;

    public static function run()
    {
        if (!self::$core instanceof self) {
            self::$core = new Core();
        }
    }

    private function __construct()
    {
        \header("Content-Type:text/html; charset=utf-8");
        define('PBATIS_ROOT', \dirname(__FILE__) . '/');
        require_once PBATIS_ROOT . 'config/constant.php';
        $this->config = require PBATIS_ROOT . 'config/config.php';
        //echo json_encode($this->config);
        if ($this->config) {
            define('PBATIS_DB_CONFIG', \json_encode($this->config['db']));
        }
        /*
        if (!APP_DEBUG) {
            error_reporting(0);
        }

        register_shutdown_function(function () {
            $error = error_get_last();
            if (!empty($error)) {
                Log::write($error, 'error');
            }
        });
    */
    }


}


?>