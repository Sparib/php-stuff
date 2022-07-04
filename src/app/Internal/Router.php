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
        ],
        "api" => [
            "api" => "/"
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
        $uri = preg_replace("/<.+>/", "", trim($apiUri, "/"));

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

        if (preg_match("/<.+>/", trim($apiUri, "/")) == 1) {
            Router::$routes["api"][$uri] = [$function, $method, true];
        } else
            Router::$routes["api"][$uri] = [$function, $method, false];
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
                $path = preg_replace('~^' . $pre . '~', "", $uri);
                break;
            }
        }

        /*
         *  $routes[<ext>][<path>][0] is the path to the file or api callable
         * 
         *  $routes[api][*][1] is the allowed request method
         *  $routes[api][*][2] is an a boolean that defines if there is a path based variable
         * 
         *  $routes[resources][1] is an optional Content-Type
         */ 

        if (!key_exists($path, Router::$routes[$ext])) {
            if ($ext == "api") {
                $pathParts = explode("/", $path);
                $pathAfter = join("/", array_slice($pathParts, 0, count($pathParts) - 1)) . "/";

                if (!key_exists($pathAfter, Router::$routes[$ext]))
                    return [false, null];
                else {
                    if (!Router::$routes[$ext][$pathAfter][2]) return [false, null];
                    if ($_SERVER['REQUEST_METHOD'] != Router::$routes[$ext][$pathAfter][1]) throw new InvalidMethodException();

                    Router::$routes[$ext][$pathAfter][0](end($pathParts));
                    return [true, null];
                }
            } else return [false, null];
        }

        if ($ext == "resources" && Router::$routes[$ext][$path][1] != null) {
            header("Content-Type: " . Router::$routes[$ext][$path][1]);
        }
        
        if ($ext == "api") {
            Router::$routes[$ext][$path][0]();
            return [true, null];
        } else
            return [true, Router::$routes[$ext][$path][0]];

        return [false, null];
    }
}
