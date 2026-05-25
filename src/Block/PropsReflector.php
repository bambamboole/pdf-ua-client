<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Block;

use BackedEnum;
use Bambamboole\PdfUaClient\Attributes\ArrayOf;
use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Example;
use Bambamboole\PdfUaClient\Attributes\Format;
use Bambamboole\PdfUaClient\Attributes\Length;
use Bambamboole\PdfUaClient\Attributes\Max;
use Bambamboole\PdfUaClient\Attributes\Min;
use Bambamboole\PdfUaClient\Attributes\Pattern;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Config\TypographyConfig;
use Bambamboole\PdfUaClient\Fonts\FontRegistry;
use Bambamboole\PdfUaClient\Template\SchemaRegistry;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use stdClass;

final class PropsReflector
{
    public function __construct(private readonly ?FontRegistry $fonts = null) {}

    /**
     * @param  class-string  $class
     * @return array<string, mixed>
     */
    public function reflect(string $class): array
    {
        return $this->reflectClass($class);
    }

    /**
     * @param  class-string  $class
     * @return array<string, mixed>
     */
    public function reflectWithRefs(string $class, SchemaRegistry $registry): array
    {
        return $this->reflectClass($class, $registry);
    }

    /**
     * @param  class-string  $blockClass
     * @return array{data: array<string, mixed>, config: array<string, mixed>}
     */
    public function reflectBlock(string $blockClass): array
    {
        $reflection = new ReflectionClass($blockClass);
        $configClass = $this->resolveConfigClass($reflection);

        return [
            'data' => $this->reflectClass($blockClass),
            'config' => $this->reflectClass($configClass),
        ];
    }

    /**
     * @param  class-string  $class
     * @return array<string, mixed>
     */
    public function reflectClass(string $class, ?SchemaRegistry $registry = null): array
    {
        $reflection = new ReflectionClass($class);
        $parent = $reflection->getParentClass() ?: null;

        $schema = $this->parentHasParameters($parent)
            ? $this->reflectWithInheritance($reflection, $parent, $registry)
            : $this->reflectFlat($reflection, $registry);

        return $this->applyClassMetadata($reflection, $schema);
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @return class-string<BlockConfig>
     */
    private function resolveConfigClass(ReflectionClass $reflection): string
    {
        $attributes = $reflection->getAttributes(Block::class);
        if ($attributes === []) {
            return BlockConfig::class;
        }

        return $attributes[0]->newInstance()->config;
    }

    /**
     * @param  ReflectionClass<object>|null  $parent
     */
    private function parentHasParameters(?ReflectionClass $parent): bool
    {
        if ($parent === null) {
            return false;
        }

        $constructor = $parent->getConstructor();

        return $constructor !== null && $constructor->getParameters() !== [];
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @return array<string, mixed>
     */
    private function reflectFlat(ReflectionClass $reflection, ?SchemaRegistry $registry): array
    {
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return ['type' => 'object', 'properties' => new stdClass, 'additionalProperties' => false];
        }

        return $this->reflectParameters($constructor->getParameters(), $registry);
    }

    /**
     * @param  ReflectionClass<object>  $child
     * @param  ReflectionClass<object>  $parent
     * @return array<string, mixed>
     */
    private function reflectWithInheritance(ReflectionClass $child, ReflectionClass $parent, ?SchemaRegistry $registry): array
    {
        $parentSchema = $this->reflectClass($parent->getName(), $registry);
        $ownPart = $this->reflectParameters($this->ownPromotedParameters($child), $registry);

        if ($registry !== null) {
            $parentForRegistry = $parentSchema;
            unset($parentForRegistry['additionalProperties']);
            $parentRef = $registry->replace(lcfirst($parent->getShortName()), $parentForRegistry);

            $schema = [
                'allOf' => [$parentRef],
                'properties' => $ownPart['properties'],
                'unevaluatedProperties' => false,
            ];

            if (isset($ownPart['required'])) {
                $schema['required'] = $ownPart['required'];
            }

            return $schema;
        }

        return $this->mergeInline($parentSchema, $ownPart);
    }

    /**
     * @param  ReflectionClass<object>  $class
     * @return list<ReflectionParameter>
     */
    private function ownPromotedParameters(ReflectionClass $class): array
    {
        $constructor = $class->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $own = [];

        foreach ($constructor->getParameters() as $param) {
            if (! $param->isPromoted()) {
                continue;
            }

            if (! $class->hasProperty($param->getName())) {
                continue;
            }

            $property = $class->getProperty($param->getName());

            if ($property->getDeclaringClass()->getName() !== $class->getName()) {
                continue;
            }

            $own[] = $param;
        }

        return $own;
    }

    /**
     * @param  array<string, mixed>  $parent
     * @param  array<string, mixed>  $child
     * @return array<string, mixed>
     */
    private function mergeInline(array $parent, array $child): array
    {
        $parentProps = $parent['properties'] ?? [];
        $parentProps = $parentProps instanceof stdClass ? [] : $parentProps;
        $childProps = $child['properties'] ?? [];
        $childProps = $childProps instanceof stdClass ? [] : $childProps;

        $merged = array_merge($parentProps, $childProps);

        $required = array_values(array_unique(array_merge(
            $parent['required'] ?? [],
            $child['required'] ?? [],
        )));

        $schema = [
            'type' => 'object',
            'properties' => $merged === [] ? new stdClass : $merged,
            'additionalProperties' => false,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @param  ReflectionClass<object>  $class
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function applyClassMetadata(ReflectionClass $class, array $schema): array
    {
        foreach ($class->getAttributes(Title::class) as $attr) {
            $schema['title'] = $attr->newInstance()->text;
        }

        foreach ($class->getAttributes(Description::class) as $attr) {
            $schema['description'] = $attr->newInstance()->text;
        }

        return $schema;
    }

    /**
     * @param  list<ReflectionParameter>  $params
     * @return array<string, mixed>
     */
    private function reflectParameters(array $params, ?SchemaRegistry $registry = null): array
    {
        $properties = [];
        $required = [];

        foreach ($params as $param) {
            $properties[$param->getName()] = $this->reflectParam($param, $registry);

            if (! $param->isDefaultValueAvailable() && ! $param->allowsNull()) {
                $required[] = $param->getName();
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties === [] ? new stdClass : $properties,
            'additionalProperties' => false,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    private function reflectParam(ReflectionParameter $param, ?SchemaRegistry $registry = null): array
    {
        $type = $param->getType();
        $schema = $this->schemaForType($type, $param, $registry);
        $schema = $this->applySpecializedSchema($param, $schema);

        foreach ($param->getAttributes(Title::class) as $attr) {
            $schema['title'] = $attr->newInstance()->text;
        }
        foreach ($param->getAttributes(Description::class) as $attr) {
            $schema['description'] = $attr->newInstance()->text;
        }
        foreach ($param->getAttributes(Min::class) as $attr) {
            $schema['minimum'] = $attr->newInstance()->value;
        }
        foreach ($param->getAttributes(Max::class) as $attr) {
            $schema['maximum'] = $attr->newInstance()->value;
        }
        foreach ($param->getAttributes(Length::class) as $attr) {
            $len = $attr->newInstance();
            if ($len->min !== null) {
                $schema['minLength'] = $len->min;
            }
            if ($len->max !== null) {
                $schema['maxLength'] = $len->max;
            }
        }
        foreach ($param->getAttributes(Pattern::class) as $attr) {
            $schema['pattern'] = $attr->newInstance()->regex;
        }
        foreach ($param->getAttributes(Format::class) as $attr) {
            $schema['format'] = $attr->newInstance()->value;
        }
        foreach ($param->getAttributes(Example::class) as $attr) {
            $schema['examples'] = [$attr->newInstance()->value];
        }

        if ($param->isDefaultValueAvailable() && ! isset($schema['$ref'])) {
            $default = $param->getDefaultValue();
            $isNullable = isset($schema['type']) && is_array($schema['type']) && in_array('null', $schema['type'], true);

            if ($default instanceof BackedEnum) {
                $schema['default'] = $default->value;
            } elseif (! ($default === null && $isNullable) && (is_scalar($default) || $default === null || is_array($default))) {
                $schema['default'] = $default;
            }
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function applySpecializedSchema(ReflectionParameter $param, array $schema): array
    {
        if (! $this->isTypographyFamilyParameter($param) || $this->fonts === null || $this->fonts->all() === []) {
            return $schema;
        }

        $schema['type'] = ['string', 'null'];
        $fonts = array_values($this->fonts->all());
        $schema['enum'] = array_map(static fn ($font): string => $font->key, $fonts);
        $schema['enumNames'] = array_map(static fn ($font): string => $font->label, $fonts);

        return $schema;
    }

    private function isTypographyFamilyParameter(ReflectionParameter $param): bool
    {
        return $param->getDeclaringClass()?->getName() === TypographyConfig::class
            && $param->getName() === 'family';
    }

    /** @return array<string, mixed> */
    private function schemaForType(?ReflectionType $type, ReflectionParameter $param, ?SchemaRegistry $registry = null): array
    {
        if ($type === null) {
            return [];
        }

        if ($type instanceof ReflectionUnionType) {
            throw new InvalidArgumentException("Union types are not supported on parameter {$param->getName()}");
        }

        if (! $type instanceof ReflectionNamedType) {
            return [];
        }

        $name = $type->getName();
        $nullable = $type->allowsNull();

        $schema = match ($name) {
            'string' => ['type' => 'string'],
            'int' => ['type' => 'integer'],
            'float' => ['type' => 'number'],
            'bool' => ['type' => 'boolean'],
            'array' => $this->reflectArray($param, $registry),
            default => $this->reflectComplexType($name, $param, $registry),
        };

        if ($nullable && isset($schema['type']) && is_string($schema['type'])) {
            $schema['type'] = [$schema['type'], 'null'];
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    private function reflectArray(ReflectionParameter $param, ?SchemaRegistry $registry = null): array
    {
        foreach ($param->getAttributes(ArrayOf::class) as $attr) {
            $itemSchema = $this->arrayItemSchema($attr->newInstance()->itemTypes, $registry);

            return [
                'type' => 'array',
                'items' => $itemSchema,
            ];
        }

        return ['type' => 'array'];
    }

    /**
     * @param  non-empty-list<class-string|string>  $itemTypes
     * @return array<string, mixed>
     */
    private function arrayItemSchema(array $itemTypes, ?SchemaRegistry $registry): array
    {
        $schemas = array_map(
            fn (string $itemType): array => $this->schemaForArrayItemType($itemType, $registry),
            $itemTypes,
        );

        if (count($schemas) === 1) {
            return $schemas[0];
        }

        $types = array_column($schemas, 'type');
        if (count($types) === count($schemas) && array_all($types, fn (mixed $type): bool => is_string($type))) {
            return ['type' => array_values(array_unique($types))];
        }

        return ['anyOf' => $schemas];
    }

    /** @return array<string, mixed> */
    private function schemaForArrayItemType(string $itemType, ?SchemaRegistry $registry): array
    {
        return match ($itemType) {
            'string' => ['type' => 'string'],
            'int' => ['type' => 'integer'],
            'float' => ['type' => 'number'],
            'bool' => ['type' => 'boolean'],
            default => $registry !== null
                ? $this->reflectClass($itemType, $registry)
                : $this->reflectClass($itemType),
        };
    }

    /** @return array<string, mixed> */
    private function reflectComplexType(string $class, ReflectionParameter $param, ?SchemaRegistry $registry = null): array
    {
        if (enum_exists($class)) {
            $reflection = new ReflectionEnum($class);
            $cases = array_map(
                fn ($case) => $case->getBackingValue() ?? $case->getName(),
                $reflection->getCases(),
            );

            $backingType = $reflection->isBacked() ? $reflection->getBackingType() : null;
            $jsonType = $backingType instanceof ReflectionNamedType
                ? match ($backingType->getName()) {
                    'int' => 'integer',
                    'string' => 'string',
                    default => null,
                }
            : null;

            $schema = ['enum' => $cases];
            if ($jsonType !== null) {
                $schema = ['type' => $jsonType, 'enum' => $cases];
            }

            return $schema;
        }

        if (class_exists($class)) {
            if ($registry !== null && str_ends_with($class, 'Config')) {
                $defName = lcfirst((string) (new ReflectionClass($class))->getShortName());
                $nested = $this->reflectClass($class, $registry);

                return $registry->ref($defName, $nested);
            }

            return $this->reflectClass($class, $registry);
        }

        throw new InvalidArgumentException("Cannot reflect type {$class} on parameter {$param->getName()}");
    }
}
