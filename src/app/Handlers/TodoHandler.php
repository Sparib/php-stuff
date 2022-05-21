<?php

namespace app\Handlers;
use app\Internal\Response;

class TodoHandler {
    public static function get_todo() {
        $todos = [
            new Todo("Fix everything")
        ];
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