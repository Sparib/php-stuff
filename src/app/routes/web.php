<?php

use app\Internal\Router;

Router::get("/", "index.php");
Router::get("/o", "other.php");
Router::get('/minecraft', "mc.php");