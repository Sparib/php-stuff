<?php

namespace app\Handlers;

use app\Internal\Route;
use app\Setup;

class CDNHandler {
    #[Setup]
    public static function setup() {
        // do setup shit
    }

    #[Route("/file/create")]
    public static function create() {
        // interface with file core to create
    }

    #[Route("/file/delete", literal: false)]
    public static function delete($path) {
        // use the $path variable to delete file from hash(?)
    }

    #[Route("/file/edit", literal: false)]
    public static function edit($path) {
        // same thing as above
    }

    #[Route("/gallery")]
    public static function gallery() {
        // show gallery
    }
}