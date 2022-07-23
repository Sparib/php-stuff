<?php

require 'vendor/autoload.php';

include 'app/App.php';
include 'app/Configs/GlobalConfig.php';
use app\App;
use app\Configs\GlobalConfig;

define("__BASE_URL__", __DIR__);

if (!function_exists('app')) {
    function app() {
        static $app;
        if (!isset($app) or $app == null) $app = new App();
        return $app;
    }
}

if (isset($argc))
    app()->cli($argv);
else {
    \Sentry\init([
        'dsn' => "https://b00795da98a84a5e83ba3fca75f54ed5@sentry.morgverd.com/4",
        'traces_sample_rate' => 0
    ]);

    header("Access-Control-Allow-Origin: " . GlobalConfig::$config['host']);

    app()->run();
}
