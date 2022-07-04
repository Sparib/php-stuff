<?php

namespace app\Handlers;
use app\Internal\Response;

class TodoHandler {
    private static ?array $cached_todos = null;
    public static function get_todo() {
        if (!self::$cached_todos) { 
            self::$cached_todos = [
                new Todo("Icons look nice", true),
                new Todo("Make api run off api subdomain instead of //", true),
                new Todo("Also set the cross-origin header", true),
                new Todo("Config files?", true),
                new Todo("Dynamic api paths", true),
                new Todo("Rewrite flask as php")
            ];
        }
        $todos = self::$cached_todos;
        $return = [];
        foreach ($todos as $todo) {
            $return[$todo->task] = $todo->complete;
        }
        Response::return_json($return);
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