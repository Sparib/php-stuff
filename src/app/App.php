<?php

namespace app;

use app\Internal\Handlers\ErrorHandler;
use app\Internal\Handlers\FileHandler;
use app\Internal\Handlers\InvalidMethodException;
use app\Internal\Director;
use app\Internal\Router;
use Http\Discovery\Exception\NotFoundException;
use app\Configs\GlobalConfig;
use app\Internal\Handlers\CommandHandler;
use \Attribute;

enum Extensions {
    case http;
    case resources;
    case api;
}

class App {
    private static $parentSpan = null;

    public static array $routeExts = ["/public" => Extensions::resources, "/api" => Extensions::api, "/" => Extensions::http];
    public readonly Extensions $extension;
    public readonly bool $developer;

    public function run() {
        $op = "http.request";

        foreach (App::$routeExts as $pre => $e) {
            if (str_starts_with($_SERVER["REQUEST_URI"], $pre)) {
                $op = $e->name . ".request";
                $this->extension = $e;
                break;
            }
        }

        $this->developer = (($_GET['dev'] ?? null) == GlobalConfig::$config['dev_key']);

        // Setup full transaction context
        $transactionContext = new \Sentry\Tracing\TransactionContext();
        $transactionContext->setName('Request from ' . GlobalConfig::$config['location']);
        $transactionContext->setOp($op);
        
        $transaction = \Sentry\startTransaction($transactionContext);
        
        \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);
        
        $this->setup_error_handler();

        $this->setup_file_handler();

        Router::setup();

        $this->get_page();

        \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);
        $transaction->finish();
    }

    public static function getNewTransactionSpan($op, $desc = null) {
        App::$parentSpan = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        // Check if we have a parent span (this is the case if we started a transaction earlier)
        if (App::$parentSpan !== null) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp($op);
            if ($desc != null) $context->setDescription($desc);
            $span = App::$parentSpan->startChild($context);

            // Set the current span to the span we just started
            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        return $span;
    }

    public static function completeTransation(\Sentry\Tracing\Span $span) {
        if ($span !== null) {
            $span->finish();
    
            // Restore the current span back to the parent span
            \Sentry\SentrySdk::getCurrentHub()->setSpan(App::$parentSpan);
        }
    }

    private function setup_error_handler() {
        $span = App::getNewTransactionSpan("error_handler.setup");

        if ($this->developer) {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $whoops->register();

            // App::completeTransation($span);
            // return;
        }

        include_once __BASE_URL__ . "/app/Internal/Handlers/ErrorHandler.php";

        set_exception_handler("app\Internal\Handlers\ErrorHandler::global_handler");
        set_error_handler("app\Internal\Handlers\ErrorHandler::global_error_handler");

        ErrorHandler::add_handler(function(NotFoundException $e) {
            $code = 404;
            include Director::dir("pages") . "/error.php";
            return true;
        });

        ErrorHandler::add_handler(function(InvalidMethodException $e) {
            $code = 405;
            include Director::dir("pages") . "/error.php";
            return true;
        });

        App::completeTransation($span);
    }

    private function setup_file_handler() {
        $span = App::getNewTransactionSpan("file_handler.setup");

        include_once __BASE_URL__ . "/app/Internal/Handlers/FileHandler.php";

        $includeSpan = App::getNewTransactionSpan("file_handler.include");
        FileHandler::include_files();
        App::completeTransation($includeSpan);

        App::completeTransation($span);
    }

    private function get_page() {
        $span = App::getNewTransactionSpan("router.fetch");
        [$success, $uri, $is_attachment] = Router::fetch($_SERVER["REQUEST_URI"]) + [false, null, false];
        App::completeTransation($span);

        if (!$success)
            throw new NotFoundException();
        elseif ($this->extension == Extensions::resources)
            echo readfile($uri);
        elseif ($uri != null)
            include_once $uri;
    }

    public function cli($argv) {
        $this->setup_cli_file_handler();

        CommandHandler::runCommand($argv);

        // echo "\n";
    }

    private function setup_cli_file_handler() {
        include_once __BASE_URL__ . "/app/Internal/Handlers/FileHandler.php";
        FileHandler::include_cli_files();
    }
}

/**
 * Attached to a method that should be called before running an api function.
 */
#[Attribute(ATTRIBUTE::TARGET_METHOD)]
class Setup {}
