<?php

namespace routes;

use app\Internal\Router;
use FilesystemIterator;

loop_dir(app()->Director()->dir("resources"));

function loop_dir($dir) {
    foreach (new FilesystemIterator($dir) as $f) {
        if ($f->getType() === "file") {
            Router::resource("/public" . str_replace(__BASE_URL__ . "/resources", "", $f->getPathname()), $f->getFilename());
        } elseif ($f->getType() === "dir") {
            loop_dir($f->getPathname());
        }
    }
}