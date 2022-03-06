<?php

function isMobileDevice() {
    return preg_match(
        "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i",
        $_SERVER["HTTP_USER_AGENT"]
    );
}

// http_response_code(503);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1">
    <title>Under Maintenance</title>
    <style>
        * {
            color: #eee;
            font-family: sans-serif;
        }

        html, body {
            margin: 0;
            overflow: hidden;
        }

        body {
            background-color: #333;

            display: flex;
            flex-direction: column;
            justify-content: center;

            width: 100vw;
            height: 100vh;
            padding-left: 25%;
        }

        h1 {
            color: orangered;
            font-size: 50px;
        }

        p {
            font-size: 20px;
            line-height: 30px;
        }

        .signature {
            font-family: serif;
        }

        img {
            position: absolute;
            top: 0;
            right: 0;
            width: 420px;
        }

        code {
            background-color: #111;
            border-radius: 5px;
            font-family: monospace;
            padding: 2px 5px;
            font-size: 18px;
        }
    </style>
    <?php if (isMobileDevice()): ?>
    <style>
        body {
            padding-left: 15px;
        }

        h1 {
            font-size: 10vw;
        }

        p {
            font-size: 4vw;
        }

        code {
            font-size: 3vw;
        }

        .signature {
            font-size: 5vw;
        }

        img {
            display: none;
        }
    </style>
    <?php endif ?>
</head>
<body>
    <h1>Currently Under Maintenance!</h1>
    <p>Sorry for any inconviences this may have caused, but <code><?=$_SERVER["SERVER_NAME"]?></code> is currently under maintenance!<br>Check back later to see if the status has changed.</p>
    <p class="signature">— Sparib</p>
    <img src="https://http.cat/503" alt="">
</body>
</html>