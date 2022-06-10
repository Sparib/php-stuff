<?php

require 'vendor/autoload.php';

include 'app/App.php';
use app\App;

define("__BASE_URL__", __DIR__);

\Sentry\init([
    'dsn' => "https://b00795da98a84a5e83ba3fca75f54ed5@sentry.morgverd.com/4",
    'traces_sample_rate' => 1.0
]);

if ($_SERVER["REQUEST_URI"] == "/test") {
    include __BASE_URL__ . "/pages/test.php";
    return;
}

if (!function_exists('app')) {
    function app() {
        static $app;
        if (!isset($app) or $app == null) $app = new App();
        return $app;
    }
}

$routeExts = ["resource.request" => "/public", "api.request" => "/api//"];
$op = "http.request";
foreach ($routeExts as $e => $pre) {
    if (str_starts_with($_SERVER["REQUEST_URI"], $pre)) {
        $op = $e;
        break;
    }
}

// Setup full transaction context
$transactionContext = new \Sentry\Tracing\TransactionContext();
$transactionContext->setName('Request');
$transactionContext->setOp($op);

$transaction = \Sentry\startTransaction($transactionContext);

\Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

app()->run();

\Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);
$transaction->finish();
