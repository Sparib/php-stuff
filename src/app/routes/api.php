<?php

use app\Configs\SpotifyConfig;
use app\Configs\SQLConfig;
use app\Handlers\SpotifyHandler;
use app\Handlers\TodoHandler;
use app\Internal\Router;


Router::registerApi("spotify", SpotifyHandler::class, SpotifyConfig::class, SQLConfig::class);
Router::registerApi("todo", TodoHandler::class);
