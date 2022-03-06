<?php

namespace routes;

use app\Internal\Router;
use FilesystemIterator;

foreach (new FilesystemIterator(app()->Director()->dir("pages")) as $f) {
    if (!str_ends_with($f->getFilename(), ".php")) continue;
    if (preg_match("/\w*\.disabled\.\w*/", $f->getFilename())) continue;
    if ($f->getFilename() == "index.php") {
        Router::get("/", $f->getFilename());
    } else {
        Router::get("/" . str_replace(".php", "", $f->getFilename()), $f->getFilename());
    }
}

?>