<?php

namespace app\Handlers;

use Http\Discovery\Exception\NotFoundException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ErrorHandler {
    private $handlers = [];

    /**
     * Global exception handler, essentially the fallback
     *
     * @param \Throwable $e
     * @return never
     */
    public function global_handler(\Throwable $e): never {
        \Sentry\addBreadcrumb(
            new \Sentry\Breadcrumb(
                \Sentry\Breadcrumb::LEVEL_ERROR,
                \Sentry\Breadcrumb::TYPE_DEFAULT,
                'handler',                                                  // category
                'Error Handler',                                            // message (optional)
                ['type' => get_class($e), 'message' => $e->getMessage()]    // data (optional)
            )
        );

        $exists = False;
        foreach (array_keys($this->handlers) as $type) {
            if (get_class($e) == $type)
                if ($this->handlers[$type]($e))
                    die();
                else
                    $exists = True;
        }

        if (!$exists) {
            $code = 500;
            include_once __BASE_URL__ . "/pages/error.php";
        }

        \Sentry\captureException($e);

        die();
    }

    public function global_error_handler($errno, $errstr, $errfile, $errline) {
        echo "Error > ", $errno, " : ", $errstr, " | In " . $errfile . " at " . $errline;
        return true;
    }

    /**
     * Adds an exception handler.
     * Accepts a callable. First parameter must be exception type, other parameters are ignored, meaning they must be optional.
     * Callable can return true to prevent default handling.
     *
     * @param callable $c
     * @return void
     */
    public function add_handler(callable $c) {
        $reflection = new \ReflectionFunction($c);
        if ($reflection->getNumberOfParameters() < 1) throw new InvalidOptionsException("Exception handler must take at least one argument!");
        if (!$reflection->getParameters()[0]->hasType()) throw new InvalidOptionsException("First parameter of exception handler must be typed!");
        $this->handlers[$reflection->getParameters()[0]->getType()->getName()] = $c;
    }
}

?>