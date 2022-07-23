<?php

use app\Handlers\TestHandler;
use app\Handlers\WorkerHandler;
use app\Internal\Handlers\CommandHandler;

CommandHandler::registerCommandHandler("worker", WorkerHandler::class);
CommandHandler::registerCommandHandler("test", TestHandler::class);