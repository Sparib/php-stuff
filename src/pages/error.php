<?php

if (!isset($code)) {
    header("Location: https://sparib.com");
    die();
}

$headers = @get_headers("https://http.cat/" . $code);

if($headers && strpos( $headers[0], '200'))
    $link = "https://http.cat/" . $code;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$code?> Error</title>
</head>
<body>
    <?php if (isset($link)): ?><img src="<?=$link?>" alt=""><?php endif ?>
</body>
</html>