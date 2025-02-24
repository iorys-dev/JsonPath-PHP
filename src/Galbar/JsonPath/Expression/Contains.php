<?php

namespace JsonPath\Expression;

class Contains
{
    const string SEPARATOR = ',';

    public static function evaluate(&$root, &$partial, $source, $lookupValue): bool
    {
        $source = Value::evaluate($root, $partial, trim($source));
        $lookupValue = self::prepareList($root, $partial, $lookupValue);

        if (is_array($source)) {
            foreach ($lookupValue as $value) {
                if (is_string($value) && preg_match('/^\/(?<regex>.*)\/(?<flags>[A-Za-z]*)$/', $value, $matches)) {
                    $pattern = '/' . $matches['regex'] . '/' . $matches['flags'];

                    foreach ($source as $sourceValue) {
                        if (preg_match($pattern, $sourceValue)) {
                            return true;
                        }
                    }
                } else {
                    if (in_array($value, $source)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private static function prepareList(&$root, &$partial, $expression): array
    {
        if (preg_match('/^\[.*]$/', $expression)) {
            $expression = substr($expression, 1, -1);
        }

        if (!str_contains($expression, self::SEPARATOR)) {
            return [Value::evaluate($root, $partial, trim($expression))];
        }

        return array_map(
            function ($value) use ($root, $partial) {
                return Value::evaluate($root, $partial, trim($value));
            },
            explode(self::SEPARATOR, $expression)
        );
    }
}

