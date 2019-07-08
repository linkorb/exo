<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Exo\Utils;

$run = Utils::run(function($request) {
    $input = $request['input'];
    $greeting = $input['greeting'];
    $name = $input['name'];
    $color = getenv('EXO__EXAMPLE__COLOR') ?? 'undefined';

    $text = $greeting . ', ' . $name . '! (' . $color . ')';
    return [
        'status' => 'OK',
        'output' => [
            'text' => $text
        ]
    ];
});
