<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Support;

use BackedEnum;
use Bambamboole\PdfUaClient\Attributes\ArrayOf;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;

final class ValueObjectHydrator
{
    /**
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $data
     * @return T
     *
     * @template T of object
     */
    public function hydrate(string $class, array $data, bool $requireProvided = false): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $data)) {
                $args[$name] = $this->coerce($param, $data[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[$name] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $args[$name] = null;
            } elseif ($requireProvided) {
                throw new InvalidArgumentException("Missing required prop: {$name}");
            }
        }

        return $reflection->newInstanceArgs($args);
    }

    private function coerce(ReflectionParameter $param, mixed $value): mixed
    {
        $type = $param->getType();
        if (! $type instanceof ReflectionNamedType) {
            return $value;
        }

        $name = $type->getName();

        if (enum_exists($name)) {
            $enumReflection = new ReflectionEnum($name);
            if ($enumReflection->isBacked() && (is_string($value) || is_int($value))) {
                /** @var class-string<BackedEnum> $name */
                return $name::from($value);
            }
            if (is_string($value)) {
                foreach ($enumReflection->getCases() as $case) {
                    if ($case->getName() === $value) {
                        return $case->getValue();
                    }
                }
            }
        }

        if ($name === 'array' && is_array($value)) {
            foreach ($param->getAttributes(ArrayOf::class) as $attr) {
                /** @var class-string $itemClass */
                $itemClass = $attr->newInstance()->itemClass;
                if (! class_exists($itemClass)) {
                    return $value;
                }

                return array_map(fn ($item) => $this->hydrate($itemClass, $item), $value);
            }
        }

        if (class_exists($name) && is_array($value)) {
            return $this->hydrate($name, $value);
        }

        return $value;
    }
}
