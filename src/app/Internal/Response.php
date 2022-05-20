<?php

class Response {
    public static function return_json($array, $code = 200): never {
        header("Content-Type: application/json");
        http_response_code($code);
        echo json_encode($array);
        exit();
    }
}

?>