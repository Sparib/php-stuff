<?php

namespace app\Internal\Handlers;

use app\App;
use app\Extensions;
use app\Internal\Director;

class FileHandler {
    private static $routeNames = ["web.php" => Extensions::http, "resources.php" => Extensions::resources, "api.php" => Extensions::api];

    public static function include_files() {
        include_once __BASE_URL__ . "/app/Internal/Director.php";

        foreach (Director::$loads as $path) {
            $span = App::getNewTransactionSpan("file_handler.file", $path);
            $origin = $path;
            if (!str_starts_with($path, "/")) $path = "/${path}";
            if (!str_starts_with($path, "/app")) $path = "/app${path}";
            $path = __BASE_URL__ . $path . ".php";
            if (!is_file($path)) {
                \Sentry\addBreadcrumb(
                    new \Sentry\Breadcrumb(
                        \Sentry\Breadcrumb::LEVEL_WARNING,
                        \Sentry\Breadcrumb::TYPE_DEFAULT,
                        'loads',
                        "Load path does not point to file.",
                        ['original' => $origin, 'full' => $path]
                    )
                );
            } else
                include_once $path;
            App::completeTransation($span);
        }

        // if ($has_error) ErrorHandler::nonbreaking("One or more nonbreaking errors encountered during file loading.", \Sentry\Severity::warning());

        if (is_file(Director::dir('routes') . "/lazy_routes.php")) {
            include_once Director::dir('routes') . "/lazy_routes.php";
            return;
        }

        include_once Director::dir('routes') . "/" . array_search(app()->extension, FileHandler::$routeNames);
    }

    public static function include_cli_files() {
        include_once __BASE_URL__ . "/app/Internal/Director.php";

        foreach (Director::$cli_loads as $path) {
            $origin = $path;
            if (!str_starts_with($path, "/")) $path = "/${path}";
            if (!str_starts_with($path, "/app")) $path = "/app${path}";
            $path = __BASE_URL__ . $path . ".php";
            if (!is_file($path)) {
                echo $path, " does not point to file.";
            } else include_once $path;
        }

        include_once Director::dir('routes') . "/cli.php";
        require __BASE_URL__ . '/vendor/predis/predis/autoload.php';
    }

    public static function path_from_class(string $class) {
        return __BASE_URL__ . "/" . str_replace("\\", "/", $class) . ".php";
    }
}

?>