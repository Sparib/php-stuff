<?php

namespace app\Handlers;

use Http\Discovery\Exception\NotFoundException;

class ErrorHandler {
    /**
     * Global exception handler, essentially the fallback
     *
     * @param \Throwable $e
     * @return never
     */
    function global_handler(\Throwable $e): never {
        // echo "Exception > ", $e->getCode(), " : ", $e->getMessage(), " | In ", $e->getFile(), " at ", $e->getLine();
        \Sentry\captureException($e);
        $code = 500;
        include_once __BASE_URL__ . "/pages/error.php";
        die();
    }

    function global_error_handler($errno, $errstr, $errfile, $errline) {
        echo "Error > ", $errno, " : ", $errstr, " | In " . $errfile . " at " . $errline;
        return true;
    }
    
    public function handle_error($code) {
        include(__BASE_URL__ . "/pages/error.php");
        die();
    }
}

?>