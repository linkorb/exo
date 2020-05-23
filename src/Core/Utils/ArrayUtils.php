<?php

namespace Exo\Core\Utils;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

class ArrayUtils
{
    public static function getByPrefix(array $variables, string $prefix)
    {
        $res = [];
        foreach ($variables as $k => $v) {
            if (substr($k, 0, strlen($prefix)) == $prefix) {
                $k = substr($k, strlen($prefix));
                $res[$k] = $v;
            }
        }
        return $res;
    }
}
