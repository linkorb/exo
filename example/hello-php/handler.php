<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Exo\Utils;

$run = Utils::run(__DIR__ . '/exo.action.json', function($input) {
    $greeting = $input['greeting'];
    $name = $input['name'];

    $text = $greeting . ', ' . $name . '!';
    return ['text' => $text];
});
