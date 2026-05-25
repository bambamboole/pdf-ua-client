<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Support;

use stdClass;

final class SchemaAwareNormalizer
{
    /** @param array<string, mixed> $schema */
    public static function normalize(mixed $data, array $schema): mixed
    {
        $defs = is_array($schema['$defs'] ?? null) ? $schema['$defs'] : [];

        /** @var array<string, array<string, mixed>> $defs */
        return self::walk($data, $schema, $defs);
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, array<string, mixed>>  $defs
     */
    private static function walk(mixed $value, array $node, array $defs): mixed
    {
        if (isset($node['$ref']) && is_string($node['$ref'])) {
            $resolved = self::resolveRef($node['$ref'], $defs);
            if ($resolved !== null) {
                return self::walk($value, $resolved, $defs);
            }
        }

        if (isset($node['oneOf']) && is_array($node['oneOf']) && is_array($value) && isset($value['type'])) {
            foreach ($node['oneOf'] as $branch) {
                if (! is_array($branch)) {
                    continue;
                }

                $variant = self::resolveVariant($branch, $defs);
                if ($variant !== null && self::branchMatchesType($branch, $variant, (string) $value['type'], $defs)) {
                    return self::walk($value, $variant, $defs);
                }
            }
        }

        $effective = self::flattenAllOf($node, $defs);

        if (self::isObjectType($effective) && is_array($value)) {
            $obj = new stdClass;
            $properties = is_array($effective['properties'] ?? null) ? $effective['properties'] : [];
            foreach ($value as $key => $childValue) {
                $childSchema = is_string($key) && isset($properties[$key]) && is_array($properties[$key])
                    ? $properties[$key]
                    : [];
                $obj->{$key} = self::walk($childValue, $childSchema, $defs);
            }

            return $obj;
        }

        if (self::isArrayType($effective) && is_array($value) && isset($effective['items']) && is_array($effective['items'])) {
            $items = $effective['items'];

            return array_map(fn (mixed $v): mixed => self::walk($v, $items, $defs), $value);
        }

        return $value;
    }

    /**
     * Walk allOf branches and merge their properties into the node so that
     * inherited properties (from parent schemas) are discoverable when
     * normalizing children.
     *
     * @param  array<string, mixed>  $node
     * @param  array<string, array<string, mixed>>  $defs
     * @return array<string, mixed>
     */
    private static function flattenAllOf(array $node, array $defs): array
    {
        if (! isset($node['allOf']) || ! is_array($node['allOf'])) {
            return $node;
        }

        $merged = $node;
        $properties = is_array($merged['properties'] ?? null) ? $merged['properties'] : [];

        foreach ($node['allOf'] as $branch) {
            if (! is_array($branch)) {
                continue;
            }

            $resolved = isset($branch['$ref']) && is_string($branch['$ref'])
                ? (self::resolveRef($branch['$ref'], $defs) ?? $branch)
                : $branch;

            $resolved = self::flattenAllOf($resolved, $defs);

            if (isset($resolved['properties']) && is_array($resolved['properties'])) {
                // Child properties win on key collisions (e.g. a type const overrides
                // the parent's loose `type: string` schema for that same key).
                $properties = $properties + $resolved['properties'];
            }
        }

        if ($properties !== []) {
            $merged['properties'] = $properties;
        }

        return $merged;
    }

    /**
     * Resolve a `oneOf` branch to a usable variant schema.
     *
     * Supports both `{$ref: ...}` (the inheritance-aware shape) and the
     * legacy `{if: ..., then: {$ref: ...}}` discriminator shape.
     *
     * @param  array<string, mixed>  $branch
     * @param  array<string, array<string, mixed>>  $defs
     * @return array<string, mixed>|null
     */
    private static function resolveVariant(array $branch, array $defs): ?array
    {
        if (isset($branch['$ref']) && is_string($branch['$ref'])) {
            return self::resolveRef($branch['$ref'], $defs);
        }

        if (isset($branch['then']) && is_array($branch['then'])) {
            if (isset($branch['then']['$ref']) && is_string($branch['then']['$ref'])) {
                return self::resolveRef($branch['then']['$ref'], $defs);
            }

            return $branch['then'];
        }

        return null;
    }

    /**
     * Match a oneOf branch against the runtime `type` value.
     *
     * Two shapes are supported:
     *   - inheritance-aware: `{ $ref: <perBlockDef> }` — discriminator comes
     *     from the referenced def's flattened `properties.type.const`.
     *   - legacy: `{ if: { properties: { type: { const: ... } } }, then: ... }`.
     *
     * @param  array<string, mixed>  $branch
     * @param  array<string, mixed>  $variant
     * @param  array<string, array<string, mixed>>  $defs
     */
    private static function branchMatchesType(array $branch, array $variant, string $type, array $defs): bool
    {
        $legacyConst = $branch['if']['properties']['type']['const'] ?? null;
        if (is_string($legacyConst)) {
            return $legacyConst === $type;
        }

        $flattened = self::flattenAllOf($variant, $defs);
        $const = $flattened['properties']['type']['const'] ?? null;

        return is_string($const) && $const === $type;
    }

    /**
     * @param  array<string, array<string, mixed>>  $defs
     * @return array<string, mixed>|null
     */
    private static function resolveRef(string $ref, array $defs): ?array
    {
        if (! str_starts_with($ref, '#/$defs/')) {
            return null;
        }
        $name = substr($ref, strlen('#/$defs/'));

        return $defs[$name] ?? null;
    }

    /** @param array<string, mixed> $node */
    private static function isObjectType(array $node): bool
    {
        $type = $node['type'] ?? null;
        if ($type === 'object') {
            return true;
        }
        if (is_array($type) && in_array('object', $type, true)) {
            return true;
        }

        return isset($node['properties']);
    }

    /** @param array<string, mixed> $node */
    private static function isArrayType(array $node): bool
    {
        $type = $node['type'] ?? null;
        if ($type === 'array') {
            return true;
        }

        return is_array($type) && in_array('array', $type, true);
    }
}
