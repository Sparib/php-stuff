<?php

namespace app\Internal\Handlers;

use Http\Discovery\Exception;
use InvalidArgumentException;

class ErrorHandler {
    private static $handlers = [];

    /**
     * Global exception handler, essentially the fallback
     *
     * @param \Throwable $e
     * @return never
     */
    public static function global_handler(\Throwable $e): never {
        $throw_sentry = True;
        if (array_key_exists(get_class($e), ErrorHandler::$handlers)) {
            foreach (ErrorHandler::$handlers[get_class($e)] as $c) {
                if ($c($e)) $throw_sentry = False;
            }
        } else {
            $code = 500;
            include_once __BASE_URL__ . "/pages/error.php";
        }

        if ($throw_sentry) \Sentry\captureException($e);

        die();
    }

    public static function global_error_handler($errno, $errstr, $errfile, $errline) {
        \Sentry\captureMessage("Error > $errno : $errstr | In $errfile at $errline", \Sentry\Severity::warning());
        return true;
    }

    public static function nonbreaking($message, \Sentry\Severity $severity) {
        \Sentry\captureMessage($message, $severity);
    }

    /**
     * Adds an exception handler.
     * Accepts a callable. First parameter must be exception type, other parameters are ignored, meaning they must be optional.
     * Callable can return true to prevent throwing to sentry.
     *
     * @param callable $c
     * @return void
     */
    public static function add_handler(callable $c) {
        $reflection = new \ReflectionFunction($c);
        if ($reflection->getNumberOfParameters() < 1) throw new InvalidArgumentException("Exception handler must take at least one argument!");
        if (!$reflection->getParameters()[0]->hasType()) throw new InvalidArgumentException("First parameter of exception handler must be typed!");
        $type = $reflection->getParameters()[0]->getType()->getName();
        if (!array_key_exists($type, ErrorHandler::$handlers)) ErrorHandler::$handlers[$type] = [];
        array_push(ErrorHandler::$handlers[$type], $c);
    }
}

/**
 * Thrown when a call to a uri is not of an acceptable method
 */
class InvalidMethodException extends \RuntimeException implements Exception {}

?>