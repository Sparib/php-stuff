<?php

echo("hi");

$uriParts = explode(".", $_SERVER["HTTP_HOST"]);
echo($uriParts);
$subdomain = join(".", array_slice($uriParts, 0, count($uriParts) - 2));
echo($subdomain)

?>