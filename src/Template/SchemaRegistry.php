<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

final class SchemaRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $defs = [];

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, string>
     */
    public function ref(string $name, array $schema): array
    {
        if (! isset($this->defs[$name])) {
            $this->defs[$name] = $schema;
        }

        return ['$ref' => "#/\$defs/{$name}"];
    }

    /**
     * Register or replace a definition. Used for parent schemas in
     * inheritance composition, where a previously registered "strict"
     * version must be replaced by the lax (no `additionalProperties`)
     * version so children can extend it via `allOf`.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, string>
     */
    public function replace(string $name, array $schema): array
    {
        $this->defs[$name] = $schema;

        return ['$ref' => "#/\$defs/{$name}"];
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return $this->defs;
    }

    public function has(string $name): bool
    {
        return isset($this->defs[$name]);
    }
}
