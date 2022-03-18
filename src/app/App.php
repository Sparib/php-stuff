<?php

namespace app;

use app\Handlers\ErrorHandler;
use app\Internal\Router;
use app\Internal\Director;
use FilesystemIterator;
use Http\Discovery\Exception\NotFoundException;
use InvalidArgumentException;

class App {

    public readonly Director $director;

    private readonly ErrorHandler $error_handler;

    public function run() {
        $this->setup_error_handler();

        $this->create_director();

        $this->include_files();

        $this->get_page();
    }

    private function create_director() {
        include_once __BASE_URL__ . '/app/Internal/Director.php';
        $this->director = new Director();
    }

    private function include_files() {
        $this->loop_dir($this->director->dir("internal"));
        $this->loop_dir($this->director->dir("handlers"));

        include_once $this->director->dir("routes") . "/resources.php";
        
        if (file_exists($this->director->dir("routes") . "/lazy_routes.php")) {
            include_once $this->director->dir("routes") . "/lazy_routes.php";
            return;
        } else
            $this->loop_dir($this->director->dir("routes"));
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

    private function setup_error_handler() {
        include_once __BASE_URL__ . "/app/Handlers/ErrorHandler.php";
        $this->error_handler = new ErrorHandler();
        set_exception_handler(array($this->error_handler, "global_handler"));
        set_error_handler(array($this->error_handler, "global_error_handler"));
    }

    private function get_page() {
        if (file_exists($this->director->dir("pages") . "/maintenance.php")) {
            include_once $this->director->dir("pages") . "/maintenance.php";
            return;
        }

        $uri = Router::fetch($_SERVER["REQUEST_URI"]);

        if (!$uri)
            throw new InvalidArgumentException();
        else
            include_once $uri;
    }
}