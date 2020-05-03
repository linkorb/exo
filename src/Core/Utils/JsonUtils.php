<?php

namespace Exo\Core\Utils;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

class JsonUtils
{
    public static function toJson(array $data)
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) . PHP_EOL;
    }

    public static function validateArray(array &$data, array $schema): void
    {
        $validator = new Validator();

        $obj = json_decode(json_encode($data));
        if (is_array($obj)) {
            // this happens when converting empty json arrays
            $obj = new \stdClass();
        }

        $validator->validate(
            $obj,
            $schema,
            Constraint::CHECK_MODE_COERCE_TYPES|Constraint::CHECK_MODE_APPLY_DEFAULTS|Constraint::CHECK_MODE_EXCEPTIONS
        );
    }
}
