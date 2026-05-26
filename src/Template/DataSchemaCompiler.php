<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use stdClass;

final readonly class DataSchemaCompiler
{
    public function __construct(
        private PropsReflector $reflector,
        private BlockRegistry $registry,
        private TemplateFactory $factory,
    ) {}

    /**
     * @param  Template|array<string, mixed>  $template
     * @return array<string, mixed>
     */
    public function dataSchemaFor(Template|array $template): array
    {
        $built = is_array($template) ? $this->factory->fromArray($template) : $template;

        return $this->compile($built);
    }

    /** @return array<string, mixed> */
    public function compile(Template $template): array
    {
        $properties = [];
        $required = [];

        foreach ($this->dataRows($template) as $row) {
            foreach ($row->blocks as $block) {
                $dataSchema = $this->dataSchemaForBlock($block);

                if ($this->hasNoProperties($dataSchema)) {
                    continue;
                }

                $id = (string) $block->id;
                $defaults = $template->data->defaults[$id] ?? [];
                $constants = $template->data->constants[$id] ?? [];
                $properties[$id] = $this->withDataLayerAnnotations($dataSchema, $defaults, $constants);

                if (isset($dataSchema['required']) && $this->uncoveredRequired($dataSchema, $defaults, $constants) !== []) {
                    $required[] = $id;
                }
            }
        }

        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$id' => 'https://pdfuakit.com/schemas/pdf-ua-client-template-data-v1.json',
            'type' => 'object',
            'properties' => $properties === [] ? new stdClass : $properties,
            'additionalProperties' => false,
        ];

        if ($required !== []) {
            $schema['required'] = array_values(array_unique($required));
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    private function dataSchemaForBlock(BlockInstance $block): array
    {
        if ($block->type === 'key-value' && isset($block->config['fields']) && is_array($block->config['fields'])) {
            return $this->keyValueDataSchema($block->config['fields']);
        }

        return $this->reflector->reflectBlock($this->registry->resolve($block->type))['data'];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function keyValueDataSchema(array $fields): array
    {
        $properties = [];
        $required = [];

        foreach ($fields as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $properties[$key] = [
                'type' => ['string', 'number', 'integer', 'boolean', 'null'],
                'title' => (string) ($field['label'] ?? $key),
            ];
            $required[] = $key;
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

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $constants
     * @return array<string, mixed>
     */
    private function withDataLayerAnnotations(array $schema, array $defaults, array $constants): array
    {
        if (! isset($schema['properties']) || ! is_array($schema['properties'])) {
            return $schema;
        }

        foreach ($defaults as $key => $value) {
            if (! isset($schema['properties'][$key]) || ! is_array($schema['properties'][$key])) {
                continue;
            }

            $schema['properties'][$key]['default'] = $value;
        }

        foreach ($constants as $key => $value) {
            if (! isset($schema['properties'][$key]) || ! is_array($schema['properties'][$key])) {
                continue;
            }

            $schema['properties'][$key]['const'] = $value;
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $constants
     * @return list<string>
     */
    private function uncoveredRequired(array $schema, array $defaults, array $constants): array
    {
        $required = $schema['required'] ?? [];
        if (! is_array($required)) {
            return [];
        }

        return array_values(array_filter(
            $required,
            static fn (mixed $field): bool => is_string($field) && ! array_key_exists($field, $defaults) && ! array_key_exists($field, $constants),
        ));
    }

    /** @return list<Row> */
    private function dataRows(Template $template): array
    {
        return [
            ...$template->rows,
            ...$template->config->page->footer->rows,
        ];
    }

    /** @param array<string, mixed> $schema */
    private function hasNoProperties(array $schema): bool
    {
        $properties = $schema['properties'] ?? null;

        return $properties === null || $properties instanceof stdClass || $properties === [];
    }
}
