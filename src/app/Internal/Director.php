<?php

namespace app\Internal;

use InvalidArgumentException;

class Director {
    /**
     * List of files to load in order of priority.
     *
     * @var array
     */
    public static $loads = [
        "Internal/Director",
        "Internal/Router",
        "Internal/Response"
    ];

    public static $cli_loads = [
        "Internal/Handlers/CommandHandler"
    ];

    private static $fileDir = [
        "app" => __BASE_URL__ . "/app",
        "internal" => __BASE_URL__ . "/app/Internal",
        "handlers" => __BASE_URL__ . "/app/Handlers",
        "i_handlers" => __BASE_URL__ . "/app/Internal/Handlers",
        "routes" => __BASE_URL__ . "/app/routes",
        "configs" => __BASE_URL__ . "/app/configs",
        "pages" => __BASE_URL__ . "/pages",
        "resources" => __BASE_URL__ . "/resources"
    ];

    public static function dir($name) {
        if (array_key_exists(strtolower($name), Director::$fileDir)) {
            return Director::$fileDir[$name];
        } else {
            throw new InvalidArgumentException("Requested dir index does not exist!");
        }
    }
}
