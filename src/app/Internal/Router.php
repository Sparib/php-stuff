<?php

namespace app\Internal;

use app\Internal\Handlers\ErrorHandler;
use app\Internal\Handlers\FileHandler;
use app\Configs\GlobalConfig;
use app\Internal\Director;
use app\Extensions;
use app\App;
use app\Setup;
use Attribute;

class Router {
    private static $routes;

    public static function setup() {
        if (!isset(Router::$routes)) Router::$routes = [Extensions::http->name => [], Extensions::resources->name => [], Extensions::api->name => []];
    }

    public static function get($uri, $fileName) {
        $name = (str_ends_with($fileName, ".php") ? $fileName : $fileName . ".php");
        Router::$routes[Extensions::http->name][$uri] = array(Director::dir("pages") . "/$fileName");
    }

    public static function resource($uri, $filePath, $content_type, $is_attachment = false) {
        Router::$routes[Extensions::resources->name][$uri] = array($filePath, $content_type, $is_attachment);
    }

    /**
     * Registers an api
     * 
     * @param string $apiName The name of the api to be put after '/api/' in the url
     * @param string $apiClass The handler class to handle requests
     * @param array $apiConfigs The config configs to be loaded when a request is received
     */
    public static function registerApi(string $apiName, string $apiClass, string ...$apiConfigs): void {
        if (str_contains($apiName, "/")) {
            ErrorHandler::nonbreaking("Api with name '$apiName' contains a slash. This api cannot be registered!", \Sentry\Severity::error());
            throw new \Exception("Api with name '$apiName' contains a slash. This api cannot be registered!");
            return;
        }

        if (!file_exists(FileHandler::path_from_class($apiClass))) {
            ErrorHandler::nonbreaking("Api class '$apiClass' does not exist. The '$apiName' api cannot be registered!", \Sentry\Severity::error());
            throw new \Exception("Api class '$apiClass' does not exist. The '$apiName' api cannot be registered!");
            return;
        }

        foreach ($apiConfigs as $apiConfig) {
            if (!file_exists(FileHandler::path_from_class($apiConfig))) {
                ErrorHandler::nonbreaking("Api config '$apiConfig' does not exist. The '$apiName' api will not be registered!", \Sentry\Severity::error());
                throw new \Exception("Api config '$apiConfig' does not exist. The '$apiName' api will not be registered!");
                return;
            }
        }

        Router::$routes[Extensions::api->name][$apiName] = [$apiClass, $apiConfigs];
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

        $path = preg_replace('~^' . array_search(app()->extension, App::$routeExts) . '~', "", $uri);
        if (!str_starts_with($path, "/")) $path = "/$path";

        $ext = app()->extension->name;

        // The return structure is [success, uri, is_attachment] with defaults of [false, null, false]
        // YOU MUST RETURN AN ARRAY
        // Even if it is just returning true, it must be [true]
        
        if ($ext == Extensions::http->name && file_exists(Director::dir("pages") . "/maintenance.php") && (!isset($_GET["bypass"]) || $_GET["bypass"] != GlobalConfig::$config["bypass_key"]))
            return [true, Director::dir("pages") . "/maintenance.php"];

        if ($ext == Extensions::api->name) {
            $apiParts = explode("/", $path, 3);
            $api = $apiParts[1];
            $path = "/" . ($apiParts[2] ?? "");

            if (!key_exists($api, Router::$routes[$ext])) return [false];

            /*
             * $apiInfo[0] is the handler
             * $apiInfo[1] is the config array
             */

            $apiInfo = Router::$routes[$ext][$api];

            include_once FileHandler::path_from_class($apiInfo[0]);

            if (count($apiInfo[1]) > 0) foreach ($apiInfo[1] as $config) include_once FileHandler::path_from_class($config);

            $class = new \ReflectionClass($apiInfo[0]);

            foreach ($class->getMethods(17) as $method) {
                if (count(($attr = $method->getAttributes(Route::class))) == 0) continue;
                $inst = $attr[0]->newInstance();

                $p = $inst->path; $methods = $inst->methods; $literal = $inst->literal;

                if (!in_array($_SERVER['REQUEST_METHOD'], $methods)) continue;

                $invoke_setups = function($class) {
                    foreach ($class->getMethods(17) as $method) {
                        if (count(($attr = $method->getAttributes(Setup::class))) == 0) continue;
                        $method->invoke(null);
                    }
                };

                $invoke_setups($class);

                if ($literal && $path == $p) {
                    $method->invoke(null);
                    return [true];
                }
                
                if (!$literal && str_starts_with($path, $p)) {
                    $method->invoke(null, str_replace($p, "", $path));
                    return [true];
                }
            }

            return [false];
        }

        /*
         *  $routes[<ext>][<path>][0] is the path to the file
         * 
         *  $routes[resources][*][1] is an optional Content-Type
         *  $routes[resources][*][2] is a boolean that defines if the file should be read as an attachment
         */

        if (!key_exists($path, Router::$routes[$ext])) return [false];

        if ($ext == Extensions::resources->name && Router::$routes[$ext][$path][1] != null) {
            header("Content-Type: " . Router::$routes[$ext][$path][1]);
            return [true, Router::$routes[$ext][$path][0], Router::$routes[$ext][$path][2]];
        }

        return [true, Router::$routes[$ext][$path][0]];
    }
}

#[Attribute(ATTRIBUTE::TARGET_METHOD)]
class Route {
    public readonly string $path;
    public readonly array $methods;
    public readonly bool $literal;

    public function __construct(string $path, array|string $methods = ["GET"], bool $literal = true) {
        $this->path = $path;
        $this->literal = $literal;

        if (is_string($methods)) $this->methods = [$methods];
        else $this->methods = $methods;
    }
}
