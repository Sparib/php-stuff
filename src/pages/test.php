<?php

$throwable = function (InvalidArgumentException $e) {
    return "a";
};

echo (new ReflectionFunction($throwable))->getParameters()[0]->getType();


?>