<?php

use app\Internal\Router;

Router::api("/get/todo", 'app\Handlers\TodoHandler::get_todo');
