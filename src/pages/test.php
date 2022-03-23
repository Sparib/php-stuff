<?php

function throwable(InvalidArgumentException $e) {
    return "a";
};

function catche(Throwable $e) {
    echo (new ReflectionFunction("throwable"))->getParameters()[0]->getType()->getName() == get_class($e) ? "True" : "False";
    die();
};

set_exception_handler("catche");

throw new InvalidArgumentException();


?>