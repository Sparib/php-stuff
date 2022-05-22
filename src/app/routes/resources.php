<?php

namespace routes;

use app\Internal\Router;
use FilesystemIterator;


loop_dir(app()->director->dir("resources"));

function loop_dir($dir) {
    $types = ["css" => "text/css", "js" => "text/js", "images" => "image/?"];

    foreach (new FilesystemIterator($dir) as $f) {
        if ($f->getType() === "file") {
            $type = explode("/", str_replace(app()->director->dir("resources") . "/", "", $dir))[0];
            $content_type = array_key_exists($type, $types) ? $types[$type] : null;
            
            if ($type == "images") $content_type = str_replace("?", end(explode(".", $f->getFilename())), $types[$type]);
            
            Router::resource(str_replace(__BASE_URL__ . "/resources", "", $f->getPathname()), $f->getPathname(), $content_type);
        } elseif ($f->getType() === "dir") {
            loop_dir($f->getPathname());
        }
    }
}
