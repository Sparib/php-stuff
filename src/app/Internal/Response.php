<?php

namespace app\Internal;

class Response {
    public static function return_json($array, $code = 200){
        header("Content-Type: application/json");
        http_response_code($code);
        if (!array_key_exists("success", $array)) $array["success"] = true;
        echo json_encode($array);
    }
}

?>