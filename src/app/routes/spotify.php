<?php

use app\Internal\Router;

Router::api("/s/uri", "app\Handlers\SpotifyHandler::get_uri");
Router::api("/s/user/<user_code>", "app\Handlers\SpotifyHandler::user");
Router::api("/s/callback", "app\Handlers\SpotifyHandler::callback");