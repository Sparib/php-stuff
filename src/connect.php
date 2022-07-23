<?php

require 'vendor/predis/predis/autoload.php';
use Predis\Client;

$client = new Client();
$value = $client->get('new') ?? "empty";
echo $value, "\n";
