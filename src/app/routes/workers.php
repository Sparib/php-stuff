<?php

use app\Configs\SpotifyConfig;
use app\Configs\SQLConfig;
use app\Handlers\SpotifyHandler;
use app\Handlers\WorkerHandler;

WorkerHandler::registerWorker("spotify", SpotifyHandler::class, SpotifyConfig::class, SQLConfig::class);