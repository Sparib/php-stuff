<?php

namespace app\Handlers;

use \WpOrg\Requests\Requests;
use app\Configs\SpotifyConfig;
use app\Configs\SQLConfig;
use app\Internal\Response;
use app\Internal\Route;
use app\Setup;
use Http\Discovery\Exception\NotFoundException;
use mysqli;

class SpotifyHandler {    
    private static string $app_auth_token;
    private static $acceptable_api_paths = [
        "/me/player" => [
            "method" => Requests::GET,
            "options" => []
        ],
        "/me/player/play" => [
            "method" => Requests::PUT,
            "options" => []
        ],
        "/me/player/pause" => [
            "method" => Requests::PUT,
            "options" => []
        ],
        "/me/player/next" => [
            "method" => Requests::POST,
            "options" => []
        ],
        "/me/player/previous" => [
            "method" => Requests::POST,
            "options" => []
        ],
        "/me/player/seek" => [
            "method" => Requests::PUT,
            "options" => ["position_ms"]
        ],
        "/me/player/repeat" => [
            "method" => Requests::PUT,
            "options" => ["state"]
        ],
        "/me/player/shuffle" => [
            "method" => Requests::PUT,
            "options" => ["state"]
        ],
        "/me/player/volume" => [
            "method" => Requests::PUT,
            "options" => ["volume_percent"]
        ]
    ];

    #[Setup]
    public static function create_app_auth() {
        if (!isset(SpotifyHandler::$app_auth_token)) {
            SpotifyHandler::$app_auth_token = "Basic " . rtrim(strtr(base64_encode(SpotifyConfig::$config['CLIENT_ID'] . ":" . SpotifyConfig::$config['CLIENT_SECRET']), '+/', '-_'), '=');
        }
    }

    #[Route("/uri")]
    public static function get_uri() {
        $state = bin2hex(random_bytes(random_int(15, 30)));
        $user_code = bin2hex(random_bytes(24));
        $hash = hash("sha256", $user_code . $state);

        $conn = SpotifyHandler::create_mysqli();

        if (!$conn) return null;

        $expire = time() + 300;

        $sql = "INSERT INTO Users (user_code, hash, expires) VALUES ('$user_code', '$hash', $expire);";

        if ($conn->query($sql)) {

            $return = array(
                "uri" => "https://accounts.spotify.com/authorize?client_id=" . SpotifyConfig::$config['CLIENT_ID'] . "&response_type=code&redirect_uri=" . SpotifyConfig::$config['CALLBACK_URI'] . "&state=$state&scope=user-modify-playback-state%20user-read-playback-state",
                "user_code" => $user_code
            );

            $conn->close();

            Response::return_json($return);
        } else {
            $conn->close();
        }
    }

    #[Route("/user", literal: false)]
    public static function user($user_code) {
        if (!app()->developer) throw new NotFoundException();
        $user_code = substr($user_code, 1);
        $hash = $_GET["h"] ?? null;

        if ($hash == null) Response::return_json(["success" => false, "error" => "h not set"], 401);

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

    #[Route("/callback")]
    public static function callback() {
        $state = $_GET["state"] ?? null;
        $code = $_GET["code"] ?? null;
        $error = $_GET["error"] ?? null;

        if ($state == null) Response::return_json(["success" => false, "error" => "State not set"], 401);
        if ($error != null) Response::return_json(["sucess" => false, "error" => $error], 401);
        if ($code  == null) Response::return_json(["success" => false, "error" => "No code"], 401);

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
            Response::return_json([$resp_body]);
        } else $conn->close();
    }

    #[Route("/run", literal: false)]
    public static function do($path) {
        $user_code = $_GET["user"] ?? null;
        $hash = $_GET["h"] ?? null;
        
        if ($user_code == null || $hash == null) Response::return_json(["success" => false, "error" => "user and h must be set."], 401); 
        if (!array_key_exists($path, SpotifyHandler::$acceptable_api_paths)) Response::return_json(["success" => false, "error" => "Unacceptable api path"], 401);

        $api_config = SpotifyHandler::$acceptable_api_paths[$path];

        $data = $_GET;
        unset($data['h']);
        unset($data['user']);
        unset($data['dev']);

        if ($path == "/me/player/play") {
            $response = Requests::get(
                "https://api.spotify.com/v1/me/player",
                ["Authorization" => "Bearer " . SpotifyHandler::get_user($user_code, $hash)["access_token"]]
            );
            $resp_body = json_decode($response->body, true);

            if ($response->status_code > 299) Response::return_json($resp_body + ["success" => false], $response->status_code);

            if ($response->status_code == 204) Response::return_json(["success" => false, "error" => "Playback device not reachable"]);

            $data = ["position_ms" => $resp_body['progress_ms']];
        }

        foreach ($api_config['options'] as $opt) {
            if (!array_key_exists($opt, $data)) Response::return_json(["success" => false, "error" => "Missing option '$opt'."], 401);
        }

        $url = "https://api.spotify.com/v1$path";

        if ($api_config['method'] != Requests::POST && count($data) > 0 && $path != "/me/player/play") {
            $url = $url . "?" . http_build_query($data);
        }

        $attach_data = ($path == "/me/player/play" || $api_config == Requests::POST);

        $response = \WpOrg\Requests\Requests::request(
            $url,
            ["Authorization" => "Bearer " . SpotifyHandler::get_user($user_code, $hash)["access_token"]],
            $attach_data ? json_encode($data) : ["foo" => "bar"],
            $api_config['method']
        );
        $resp_body = json_decode($response->body, true) ?? [];

        if ($response->status_code > 299) Response::return_json($resp_body + ["success" => false], $response->status_code);
        
        Response::return_json($resp_body);
    }

    #[Route("/clear_sql")]
    public static function clear() {
        if (!app()->developer) throw new NotFoundException();
        $conn = SpotifyHandler::create_mysqli();
        $sql = "DELETE FROM Users;";
        $succ = $conn->query($sql);
        $conn->close();
        Response::return_json(["success" => $succ], $succ ? 200 : 500);
    }

    #[Worker(300)]
    public static function worker() {
        SpotifyHandler::create_app_auth();

        $conn = SpotifyHandler::create_mysqli();
        $sql = "SELECT user_code, refresh_token, expires FROM Users;";

        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            if (time() >= $row["expires"]) {
                $response = Requests::post(
                    "https://accounts.spotify.com/api/token",
                    ["Authorization" => SpotifyHandler::$app_auth_token],
                    ["grant_type" => "refresh_token", "refresh_token" => $row["refresh_token"]]
                );

                if ($response->status_code != 200) {
                    $sql = "DELETE FROM Users WHERE refresh_token='{$row['refresh_token']}';";
                    $conn->query($sql);
                    print_r(json_decode($response->body));
                    continue;
                }

                $resp_body = json_decode($response->body, true);
                $access_token = $resp_body["access_token"];
                $expires = $resp_body["expires_in"] + time();

                $sql = "UPDATE Users SET access_token='$access_token', expires=$expires WHERE user_code='{$row['user_code']}';";
                $conn->query($sql);
            }
        }
    }
}