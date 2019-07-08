<?php

namespace Exo;

use Exo\Loader\ActionLoader;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use Symfony\Component\Dotenv\Dotenv;

class Utils
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

        echo self::toJson($response);
    }

    public static function toJson(array $data)
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) . PHP_EOL;
    }

    public static function validateArray(array &$data, array $schema): void
    {
        $validator = new Validator();

        $obj = json_decode(json_encode($data));

        $validator->validate(
            $obj,
            $schema,
            Constraint::CHECK_MODE_COERCE_TYPES|Constraint::CHECK_MODE_APPLY_DEFAULTS|Constraint::CHECK_MODE_EXCEPTIONS
        );
    }
}
