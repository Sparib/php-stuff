<?php

if (!isset($code) && !isset($_GET["code"])) {
    header("Location: https://sparib.com");
    die();
} else if (!isset($code)) {
    $code = $_GET["code"];
}

http_response_code($code);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$code?> Error</title>
    <link rel="stylesheet" href="/public/css/error.css">
</head>
<body>
    <img src="<?='https://http.cat/' . $code?>" alt="">
    <?php if (app()->Director()->error($code) != null): ?> <p><?=app()->Director()->error($code)?></p> <?php endif ?>


</body>
</html>