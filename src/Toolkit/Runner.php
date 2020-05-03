<?php

namespace Exo\Toolkit;

use Exo\Core\Utils\JsonUtils;
use Exo\Loader\ActionLoader;
use Symfony\Component\Dotenv\Dotenv;

class Runner
{
    public static function run(callable $callable)
    {
        $filename = '.env'; // current working directory
        if (file_exists($filename)) {
            $dotenv = new Dotenv();
            $dotenv->load($filename);
        }

        $stdin = file_get_contents("php://stdin");
        $request = json_decode($stdin, true);

        $response = $callable($request);

        echo JsonUtils::toJson($response);
    }
}
