<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Support;

use BackedEnum;
use Bambamboole\PdfUaClient\Attributes\CssRule;
use ReflectionClass;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;

final class CssRuleEmitter
{
    /**
     * Emits whenever a property is non-null. The "skip if default" intent is realized via the property type:
     * nullable properties default to null (skipped); non-nullable properties always emit their concrete default.
     *
     * @return array<string, string>
     */
    public static function for(?object $config): array
    {
        if ($config === null) {
            return [];
        }

        $bySelector = [];

        foreach (self::orderedProperties($config) as $property) {
            if (! $property->isInitialized($config)) {
                continue;
            }
            $value = $property->getValue($config);

            if ($value === null) {
                continue;
            }

            if (is_object($value) && ! $value instanceof BackedEnum) {
                foreach (self::for($value) as $suffix => $props) {
                    $bySelector[$suffix] = isset($bySelector[$suffix])
                        ? $bySelector[$suffix].' '.$props
                        : $props;
                }

                continue;
            }

            $attributes = $property->getAttributes(CssRule::class);
            if ($attributes === []) {
                continue;
            }

            $rule = $attributes[0]->newInstance();
            $stringValue = $value instanceof BackedEnum ? (string) $value->value : (string) $value;
            $declaration = $rule->key.': '.str_replace('{value}', $stringValue, $rule->value).';';
            $suffix = $rule->selector ?? '';
            $bySelector[$suffix] = isset($bySelector[$suffix])
                ? $bySelector[$suffix].' '.$declaration
                : $declaration;
        }

        return $bySelector;
    }

    /** @return list<ReflectionProperty> */
    private static function orderedProperties(object $config): array
    {
        $reflection = new ReflectionObject($config);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->getProperties();
        }

        $properties = [];
        foreach (self::constructorParameterChain($reflection) as $param) {
            $name = $param->getName();
            if ($reflection->hasProperty($name)) {
                $properties[$name] = $reflection->getProperty($name);
            }
        }

        return array_values($properties);
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @return list<ReflectionParameter>
     */
    private static function constructorParameterChain(ReflectionClass $reflection): array
    {
        $params = [];
        $current = $reflection;

        while ($current !== false) {
            $constructor = $current->getConstructor();
            if ($constructor !== null && $constructor->getDeclaringClass()->getName() === $current->getName()) {
                foreach ($constructor->getParameters() as $param) {
                    if (! isset($params[$param->getName()])) {
                        $params[$param->getName()] = $param;
                    }
                }
            }
            $current = $current->getParentClass();
        }

        return array_values($params);
    }
}
