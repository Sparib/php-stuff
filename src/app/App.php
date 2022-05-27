<?php

namespace app;

use app\Handlers\ErrorHandler;
use app\Handlers\FileHandler;
use app\Internal\Router;
use app\Internal\Director;
use FilesystemIterator;
use Http\Discovery\Exception\NotFoundException;

class App {

    public readonly Director $director;

    private readonly FileHandler $file_handler;
    private readonly ErrorHandler $error_handler;
    private static $parentSpan = null;

    public function run() {
        $this->setup_error_handler();

        $this->setup_file_handler();

        $this->get_page();
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

        include_once __BASE_URL__ . "/app/Handlers/ErrorHandler.php";
        $this->error_handler = new ErrorHandler();
        set_exception_handler(array($this->error_handler, "global_handler"));
        set_error_handler(array($this->error_handler, "global_error_handler"));

        $this->error_handler->add_handler(function(NotFoundException $e) {
            $code = 404;
            include_once __BASE_URL__ . "/pages/error.php";
            return true;
        });

        App::completeTransation($span);
    }

    private function setup_file_handler() {
        $span = App::getNewTransactionSpan("file_handler.setup");

        include_once __BASE_URL__ . "/app/Handlers/FileHandler.php";
        $this->file_handler = new FileHandler();

        $dirSpan = App::getNewTransactionSpan("file_handler.director");
        $this->director = $this->file_handler->create_director();
        App::completeTransation($dirSpan);

        $includeSpan = App::getNewTransactionSpan("file_handler.include");
        $this->file_handler->include_files();
        App::completeTransation($includeSpan);

        App::completeTransation($span);
    }

    private function get_page() {
        \Sentry\addBreadcrumb(
            new \Sentry\Breadcrumb(
                \Sentry\Breadcrumb::LEVEL_INFO,
                \Sentry\Breadcrumb::TYPE_DEFAULT,
                'req',                                  // category
                'Page Request',                         // message (optional)
                ['page' => $_SERVER["REQUEST_URI"]]     // data (optional)
            )
        );

        if (file_exists($this->director->dir("pages") . "/maintenance.php")) {
            include_once $this->director->dir("pages") . "/maintenance.php";
            return;
        }

        $span = App::getNewTransactionSpan("router.fetch");
        $uri = Router::fetch($_SERVER["REQUEST_URI"]);
        App::completeTransation($span);

        if (!$uri)
            throw new NotFoundException();
        else
            include_once $uri;
    }

    private function loop_dir($dir) {
        foreach (new FilesystemIterator($dir) as $t) {
            if (preg_match("/\w*\.disabled\.\w*/", $t->getFilename())) continue;
            if ($t->getType() === "file") {
                include_once $t->getPathname();
            } elseif ($t->getType() === "dir") {
                $this->loop_dir($t->getPathname());
            }
        }
    }
}