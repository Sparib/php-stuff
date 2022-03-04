<?php

include 'app/App.php';
use app\App;

define("__BASE_URL__", __DIR__);

if (!function_exists('app')) {
    function app() {
        static $app;
        if (!isset($app) or $app == null) $app = new App();
        return $app;
    }
}

app()->run();