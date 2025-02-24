<?php

namespace JsonPath\Expression;

class Contains
{
    const SEPARATOR = ',';

    public static function evaluate(&$root, &$partial, $source, $lookupValue)
    {
        $source = Value::evaluate($root, $partial, trim($source));
        $lookupValue = self::prepareList($root, $partial, $lookupValue);

        if (is_array($source)) {
            if (is_array($lookupValue)) {
                return count(array_intersect($source, $lookupValue)) > 0;
            } else {
                return in_array($lookupValue, $source);
            }
        }

        if (is_string($source)) {
            if (is_array($lookupValue)) {
                foreach ($lookupValue as $value) {
                    if (strpos($source, $value) !== false) {
                        return true;
                    }
                }
            } else {
                return strpos($source, $lookupValue) !== false;
            }
        }

        return false;
    }

    private static function prepareList(&$root, &$partial, $expression)
    {
        if (strpos($expression, self::SEPARATOR) === false) {
            return [Value::evaluate($root, $partial, trim($expression))];
        }

        if (preg_match('/^\[.*]$/', $expression)) {
            $expression = substr($expression, 1, -1);
        }

        return array_map(
            function ($value) use ($root, $partial) {
                return Value::evaluate($root, $partial, trim($value));
            },
            explode(self::SEPARATOR, $expression)
        );
    }
}

