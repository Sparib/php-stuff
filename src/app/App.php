<?php

namespace app;

use app\Internal\Router;
use FilesystemIterator;

class App {

    private $director = null;

    public function run() {
        $this->create_director();

        $this->include_files();

        $this->get_page();
    }

    public function Director() {
        return $this->director;
    }

    private function create_director() {
        include_once __BASE_URL__ . '/app/Internal/Director.php';
        $this->director = new Internal\Director();
    }

    private function include_files() {
        $this->loop_dir($this->Director()->dir("internal"));
        $this->loop_dir($this->Director()->dir("routes"));
    }

    private function loop_dir($dir) {
        foreach (new FilesystemIterator($dir) as $t) {
            if ($t->getType() === "file") {
                include_once $t->getPathname();
            } elseif ($t->getType() === "dir") {
                $this->loop_dir($t->getPathname());
            }
        }
    }

    private function get_page() {
        Router::fetch($_SERVER["REQUEST_URI"]);
    }
}