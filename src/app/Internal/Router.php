<?php

namespace app\Internal;

use app\Handlers\ErrorHandler;
use app\Handlers\InvalidMethodException;
use BadMethodCallException;
use FilesystemIterator;

class Router {
    private static $routes = ["get" => [], "resources" => [], "api" => []];
    private static $subdomainRoutes = [
        "base" => [
            "resources" => "/public"
        ]
    ];

    public static function get($uri, $fileName) {
        $name = (str_ends_with($fileName, ".php") ? $fileName : $fileName . ".php");
        Router::$routes["get"][$uri] = array(app()->director->dir("pages") . "/$fileName");
    }

    public static function resource($uri, $filePath, $content_type) {
        Router::$routes["resources"][$uri] = array($filePath, $content_type);
    }

    public static function api($apiUri, $function, $method = "GET") {
        # Check if function is actually callable
        $uri = trim($apiUri, "/");

        if (!is_callable($function)) {
            $stringed = $function;

            # Handle if it is an array callable
            if (is_array($function))
                if (is_string($function[0]))
                    $stringed = "$function[0]::$function[1]";
                else
                    $stringed = get_class($function[0]) . "->$function[1]";

            ErrorHandler::nonbreaking("Function '$stringed' for uri '$uri' is not callable", \Sentry\Severity::warning());
            return;
        }

        Router::$routes["api"][$uri] = [$function, $method];
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
    public static function fetch($uri, $subdomain) {
        if (!array_key_exists($subdomain, Router::$subdomainRoutes)) {
            return;
        }

        $uri = explode("?", $uri)[0];

        $ext = "get";
        $path = $uri;
        foreach (Router::$subdomainRoutes[$subdomain] as $e => $pre) {
            if (str_starts_with($uri, $pre)) {
                $ext = $e;
                $path = str_replace($pre, "", $uri);
                break;
            }
        }

        /*
         *  pageInfo[0] is the path to the file (or api callable)
         *  this is for extensions that have associated values with paths
         *  such as the resources extension requiring a Content-Type individual to each path
         */ 
        foreach (Router::$routes[$ext] as $pageUri => $pageInfo) {
            if ($path != $pageUri) continue;

            if ($ext == "resources" && $pageInfo[1] != null) {
                header("Content-Type: $pageInfo[1]");
            }
            
            if ($ext == "api") {
                if ($_SERVER['REQUEST_METHOD'] != $pageInfo[1]) throw new InvalidMethodException();
                $pageInfo[0](); # Runs the callable attached to an api uri
                return [true, null];
            } else return [true, $pageInfo[0]];
        }

        return [false, null];
    }
}
