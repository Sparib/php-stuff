<?php

namespace app\Internal;

use FilesystemIterator;

class Router {
    private static $routes = ["get" => []];

    public static function get($uri, $fileName) {
        foreach (new FilesystemIterator(app()->Director()->dir("pages")) as $f) {
            if ($fileName == $f->getFilename()) {
                Router::$routes["get"][$uri] = $f->getPathname();
            }
        }
    }

    public static function fetch($uri) {
        foreach (array_keys(Router::$routes["get"]) as $r) {
            if ($uri == $r) {
                include_once Router::$routes["get"][$uri];
            }
        }
    }
}
