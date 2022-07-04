<?php

namespace app\Internal;

use JetBrains\PhpStorm\NoReturn;

class Response {
    public static function return_json($array, $code = 200) : NoReturn {
        header("Content-Type: application/json");
        http_response_code($code);
        if (!array_key_exists("success", $array)) $array["success"] = true;
        echo json_encode($array);
        exit($code);
    }
}

?>