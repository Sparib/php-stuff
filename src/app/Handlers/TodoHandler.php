<?php

namespace app\Handlers;
use app\Internal\Response;
use app\Internal\Route;

class TodoHandler {
    private static ?array $cached_todos = null;

    #[Route("/")]
    public static function get_todo() {
        if (!self::$cached_todos) { 
            self::$cached_todos = [
                new Todo("Icons look nice", true),
                new Todo("Make api run off api subdomain instead of //", true),
                new Todo("Also set the cross-origin header", true),
                new Todo("Config files?", true),
                new Todo("Dynamic api paths", true),
                new Todo("Add sql thing", true),
                new Todo("Rewrite apis to be better", true),
                new Todo("Also made everything more efficient", true),
                new Todo("SQL Server", true),
                new Todo("Rewrite flask as php", true),
                new Todo("CLI?", true),
                new Todo("Redis", true),
                new Todo("Worker", true),
                new Todo("Middleware"),
                new Todo("Make GMod Addon")
            ];
        }
        $todos = self::$cached_todos;
        $return = [];
        foreach ($todos as $todo) {
            $return[$todo->task] = $todo->complete;
        }
        Response::return_json(["data" => $return]);
    }
}

class Todo {
    public readonly string $task;
    public readonly bool $complete;

    function __construct($task, bool $complete = false) {
        $this->task = $task;
        $this->complete = $complete;
    }
}

?>