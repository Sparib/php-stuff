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

    public function run() {
        $this->setup_error_handler();

        $this->setup_file_handler();

        $this->get_page();
    }

    private function setup_error_handler() {
        include_once __BASE_URL__ . "/app/Handlers/ErrorHandler.php";
        $this->error_handler = new ErrorHandler();
        set_exception_handler(array($this->error_handler, "global_handler"));
        set_error_handler(array($this->error_handler, "global_error_handler"));

        $this->error_handler->add_handler(function(NotFoundException $e) {
            $code = 404;
            include_once __BASE_URL__ . "/pages/error.php";
            return true;
        });
    }

    private function setup_file_handler() {
        include_once __BASE_URL__ . "/app/Handlers/FileHandler.php";
        $this->file_handler = new FileHandler();
        $this->director = $this->file_handler->create_director();
        $this->file_handler->include_files();
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

        $uri = Router::fetch($_SERVER["REQUEST_URI"]);

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