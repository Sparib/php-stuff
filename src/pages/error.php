<?php

if (!isset($code) && !isset($_GET["code"])) {
    header("Location: https://sparib.com");
    die();
} else if (!isset($code)) {
    $code = $_GET["code"];
}

http_response_code($code);

function error($code)
{
    $errorDesc = [
        "500" => "This has been reported, and will be dealt with shortly.",
    ];

    if (array_key_exists($code, $errorDesc))
        return $errorDesc[$code];
    else
        return null;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $code ?> Error</title>
    <style>
        html,
        body {
            background-color: #111;
            margin: 0;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            width: 100vw;
            height: 100vh;
            color: white;
        }

        p {
            font-family: sans-serif;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <img src="<?= 'https://http.cat/' . $code ?>" alt="">
    <?php if (error($code) != null) : ?> <p><?= error($code) ?></p> <?php endif ?>


</body>

</html>