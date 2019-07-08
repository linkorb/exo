<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Exo\Utils;

$run = Utils::run(function($request) {
    $sql = $request['input']['sql'];
    $username = $request['config']['username'];
    $password = $request['config']['password'];
    $host = $request['config']['host'];
    $db = $request['config']['db'];

    $type = 'mysql';

    $str = $type .':host=' . $host . ';dbname=' . $db;
    $debug[] = $str;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    $pdo = new PDO($str, $username, $password, $options);
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $text = $username . ', ' . $sql;
    return [
        'status' => 'OK',
        'debug' => $debug,
        'output' => [
            'rows' => $rows
        ]
    ];
});
