<?php

namespace Exo;

use Exo\Loader\ActionLoader;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use Symfony\Component\Dotenv\Dotenv;

class Utils
{
    public static function run(string $actionFilename, callable $callable)
    {
        $filename = '.env'; // current working directory
        if (file_exists($filename)) {
            $dotenv = new Dotenv();
            $dotenv->load($filename);
        }

        $actionLoader = new ActionLoader();
        $action = $actionLoader->load($actionFilename);

        $stdin = file_get_contents("php://stdin");
        $input = json_decode($stdin, true);

        $action->validateConfig();
        $action->validateInput($input);

        $output = $callable($input);
        $action->validateOutput($output);

        echo self::toJson($output);
    }

    public static function toJson(array $data)
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }
}
