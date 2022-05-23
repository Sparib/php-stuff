<?php

use app\Internal\Director;

$thing = array(new Director(), 'get_todo');
echo get_class($thing[0]), '<br>';
if (is_array($thing))
    if (is_string($thing[0]))
        $stringed = "$thing[0]::$thing[1]";
    else
        $stringed = get_class($thing[0]) . "->$thing[1]";
else
    $stringed = $thing;

echo is_callable($thing), "<br>", $stringed;
?>

<html>
<head>
    <style>
        * {
            font-family: monospace;
        }
    </style>
</head>
</html>