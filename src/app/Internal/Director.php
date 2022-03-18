<?php

namespace app\Internal;

use InvalidArgumentException;

class Director {
    private $fileDir = [
        "app" => __BASE_URL__ . "/app",
        "internal" => __BASE_URL__ . "/app/Internal",
        "pages" => __BASE_URL__ . "/pages",
        "routes" => __BASE_URL__ . "/routes",
        "resources" => __BASE_URL__ . "/resources",
        "handlers" => __BASE_URL__ . "/app/Handlers"
    ];

    public function dir($name) {
        if (array_key_exists(strtolower($name), $this->fileDir)) {
            return $this->fileDir[$name];
        } else {
            throw new InvalidArgumentException("Requested dir index does not exist!");
        }
    }
}
