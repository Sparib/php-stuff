<?php

namespace app\Internal;

use InvalidArgumentException;

class Director {
    private $fileDirectories = [
        "app" => __BASE_URL__ . "/app",
        "internal" => __BASE_URL__ . "/app/Internal",
        "pages" => __BASE_URL__ . "/pages",
        "routes" => __BASE_URL__ . "/routes"
    ];

    public function dir($name) {
        if (array_key_exists(strtolower($name), $this->fileDirectories)) {
            return $this->fileDirectories[$name];
        } else {
            throw new InvalidArgumentException("Requested dir index does not exist!");
        }
    }
}
