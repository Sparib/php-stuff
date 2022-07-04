<?php

namespace app\Handlers;

use app\Configs\SpotifyConfig;
use app\Configs\SQLConfig;
use app\Internal\Response;
use mysqli;

class SpotifyHandler {    
    private static string $app_auth_token;
    private static $acceptable_api_paths = [
        "/me/player" => [
            "method" => "GET",
            "options" => []
        ],
        "/me/player/play" => [
            "method" => "PUT",
            "options" => []
        ],
        "/me/player/pause" => [
            "method" => "PUT",
            "options" => []
        ],
        "/me/player/next" => [
            "method" => "POST",
            "options" => []
        ],
        "/me/player/previous" => [
            "method" => "POST",
            "options" => []
        ],
        "/me/player/seek" => [
            "method" => "PUT",
            "options" => ["position_ms" => "int"]
        ],
        "/me/player/repeat" => [
            "method" => "PUT",
            "options" => ["state" => ["track", "context", "off"]]
        ],
        "/me/player/shuffle" => [
            "method" => "PUT",
            "options" => ["stte" => "boolean"]
        ],
        "/me/player/volume" => [
            "method" => "PUT",
            "options" => ["volume_percent" => "int"]
        ]
    ];

    public static function get_uri() {
        if (!isset(SpotifyHandler::$app_auth_token)) {
            SpotifyHandler::$app_auth_token = "Basic " . rtrim(strtr(base64_encode(SpotifyConfig::$config['CLIENT_ID'] . ":" . SpotifyConfig::$config['CLIENT_SECRET']), '+/', '-_'), '=');
        }
        $state = bin2hex(random_bytes(random_int(15, 30)));
        $user_code = bin2hex(random_bytes(24));
        $hash = hash("sha256", $user_code . $state);

        $conn = SpotifyHandler::create_mysqli();

        if (!$conn) return null;

        $sql = "INSERT INTO Users VALUES ('$user_code', '$hash', '', '', 0);";

        if ($conn->query($sql)) {

            $return = array(
                "uri" => "https://accounts.spotify.com/authorize?client_id=" . SpotifyConfig::$config['CLIENT_ID'] . "&response_type=code&redirect_uri=" . SpotifyConfig::$config['CALLBACK_URI'] . "&state=$state&scope=user-modify-playback-state%20user-read-playback-state",
                "user_code" => $user_code,
                "hash" => $hash
            );

            $conn->close();

            Response::return_json($return);
        } else {
            $conn->close();
        }
    }

    public static function user($user_code) {
        if (!isset($_GET["h"])) Response::return_json(["success" => false, "error" => "h not set"], 401);

        $hash = $_GET["h"];

        Response::return_json(SpotifyHandler::get_user($user_code, $hash));
    }

    private static function get_user($user_code, $hash) {
        $conn = SpotifyHandler::create_mysqli();

        $sql = "SELECT * FROM Users WHERE user_code='$user_code' AND hash='$hash';";

        $result = $conn->query($sql);

        if ($result->num_rows == 0) {
            Response::return_json(["success" => false, "error" => "Non-matching user_code and h pair"], 401);
        }

        $row = $result->fetch_assoc();

        $return = [
            "access_token" => $row["access_token"],
            "refresh_token" => $row["refresh_token"],
            "expires" => $row["expires"]
        ];

        $conn->close();

        return $return;
    }

    private static function create_mysqli() {
        return new mysqli(SQLConfig::$config["SERVERNAME"], SQLConfig::$config["USERNAME"], SQLConfig::$config["PASSWORD"], SQLConfig::$config["DATABASE"]);
    }

    public static function callback() {
        if (!isset(SpotifyHandler::$app_auth_token)) {
            SpotifyHandler::$app_auth_token = "Basic " . rtrim(strtr(base64_encode(SpotifyConfig::$config['CLIENT_ID'] . ":" . SpotifyConfig::$config['CLIENT_SECRET']), '+/', '-_'), '=');
        }
        if (!isset($_GET["state"])) Response::return_json(["success" => false, "error" => "State not set"], 401);
        if (isset($_GET["error"])) Response::return_json(["sucess" => false, "error" => $_GET["error"]], 401);
        if (!isset($_GET["code"])) Response::return_json(["success" => false, "error" => "No code"], 401);

        $state = $_GET["state"];
        $code = $_GET["code"];

        $conn = SpotifyHandler::create_mysqli();

        $sql = "SELECT user_code, hash FROM Users;";

        $result = $conn->query($sql);
        $user_code = null;

        while ($row = $result->fetch_assoc()) {
            $hash = hash("sha256", $row["user_code"] . $state);
            if ($hash == $row["hash"]) {
                $user_code = $row["user_code"];
                break;
            }
        }

        if ($user_code == null) Response::return_json(["success" => false, "error" => "State does not have a match"], 401);

        $response = \WpOrg\Requests\Requests::post("https://accounts.spotify.com/api/token", ["Authorization" => SpotifyHandler::$app_auth_token], ["code" => $code, "redirect_uri" => SpotifyConfig::$config["CALLBACK_URI"], "grant_type" => "authorization_code"]);
        $resp_body = json_decode($response->body, true);

        $access_token = $resp_body["access_token"];
        $refresh_token = $resp_body["refresh_token"];
        $expires = time() + $resp_body["expires_in"];
        $sql = "UPDATE Users SET access_token='$access_token', refresh_token='$refresh_token', expires=$expires WHERE user_code='$user_code';";

        if ($conn->query($sql)) {
            $conn->close();
            Response::return_json([]);
        } else $conn->close();
    }
}