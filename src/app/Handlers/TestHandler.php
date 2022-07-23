<?php

namespace app\Handlers;

use app\Internal\Handlers\Command;

class TestHandler {
    #[Command("default")]
    public static function test() {
        register_shutdown_function("app\Handlers\TestHandler::onDie");
    }

    public static function onDie() {
        echo "a\n";
    }
}