<?php

namespace app\routes;

use app\Internal\Router;
use app\Internal\Director;
use FilesystemIterator;


loop_dir(Director::dir("resources"));

function loop_dir($dir) {
    $types = ["css" => "text/css", "js" => "text/javascript", "images" => "image/?", "zip" => "application/zip"];

    foreach (new FilesystemIterator($dir) as $f) {
        if ($f->getType() === "file") {
            $type = explode("/", str_replace(Director::dir("resources") . "/", "", $dir))[0];
            $content_type = array_key_exists($type, $types) ? $types[$type] : null;
            
            if ($type == "images") {
                $tmp = explode(".", $f->getFilename());
                $content_type = str_replace("?", end($tmp), $types[$type]);
            }
            
            Router::resource(str_replace(__BASE_URL__ . "/resources", "", $f->getPathname()), $f->getPathname(), $content_type);
        } elseif ($f->getType() === "dir") {
            loop_dir($f->getPathname());
        }
    }
}
