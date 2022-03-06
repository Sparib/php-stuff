<?php

namespace app\Internal;

use FilesystemIterator;

class Router {
    private static $routes = ["get" => [], "public" => []];

    public static function get($uri, $fileName) {
        $name = (str_ends_with($fileName, ".php") ? $fileName : $fileName . ".php");
        foreach (new FilesystemIterator(app()->Director()->dir("pages")) as $f) {
            if ($fileName == $f->getFilename()) {
                Router::$routes["get"][$uri] = $f->getPathname();
            }
        }
    }

    public static function resource($uri, $fileName) {
        $file = Router::loop_dir(app()->Director()->dir("resources"), $fileName);
        if ($file != null) {
            Router::$routes["public"][$uri] = $file->getPathname();
        }
    }

    private static function loop_dir($dir, $fileName) {
        foreach (new FilesystemIterator($dir) as $f) {
            if ($f->getType() === "file") {
                if ($fileName == $f->getFilename())
                    return $f;
            } elseif ($f->getType() === "dir") {
                $f = Router::loop_dir($f->getPathname(), $fileName);
                if ($f != null)
                    return $f;
            }
        }

        return null;
    }

    /**
     * Fetch a route for a GET request
     * Returns if the route exists
     * 
     * @param string  $uri
     * 
     * @return bool
     */
    public static function fetch($uri) {
        $uri = explode("?", $uri)[0];
        if (str_starts_with($uri, "/public")) {
            foreach (array_keys(Router::$routes["public"]) as $r) {
                if ($uri == $r) {
                    $content_type = "text/" . explode(".", $uri)[1];
                    header("Content-Type: " . $content_type);
                    include_once Router::$routes["public"][$uri];
                    return true;
                }
            }
        } else {
            foreach (array_keys(Router::$routes["get"]) as $r) {
                if ($uri == $r) {
                    include_once Router::$routes["get"][$uri];
                    return true;
                }
            }
        }

        return false;
    }
}
