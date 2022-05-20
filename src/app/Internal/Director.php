<?php

namespace app\Internal;

use InvalidArgumentException;

class Director {
    /**
     * List of files to load in order of priority.
     *
     * @var array
     */
    public readonly array $loads;

    function __construct() {
        $this->loads = [
            "Handlers/ErrorHandler",
            "Internal/Director",
            "Internal/Router",
            "Internal/Response"
        ];
    }

    private $fileDir = [
        "app" => __BASE_URL__ . "/app",
        "internal" => __BASE_URL__ . "/app/Internal",
        "handlers" => __BASE_URL__ . "/app/Handlers",
        "routes" => __BASE_URL__ . "/app/routes",
        "pages" => __BASE_URL__ . "/pages",
        "resources" => __BASE_URL__ . "/resources"
    ];

    public function dir($name) {
        if (array_key_exists(strtolower($name), $this->fileDir)) {
            return $this->fileDir[$name];
        } else {
            throw new InvalidArgumentException("Requested dir index does not exist!");
        }
    }
}
