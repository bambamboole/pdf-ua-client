<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Contracts\HasDynamicData;
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
                $dataSchema = $this->withDataLayerAnnotations($dataSchema, $defaults, $constants);

                if ($this->hasNoProperties($dataSchema)) {
                    continue;
                }

                $properties[$id] = $dataSchema;

                if ($this->requiresBlockData($dataSchema)) {
                    $required[] = $id;
                }
            }
        }

        if ($template->attachmentRequirements !== []) {
            $properties['attachments'] = $this->attachmentsSchema($template->attachmentRequirements);

            if ($this->hasRequiredAttachmentRequirements($template->attachmentRequirements)) {
                $required[] = 'attachments';
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

    /**
     * @param  list<TemplateAttachmentRequirement>  $requirements
     * @return array<string, mixed>
     */
    private function attachmentsSchema(array $requirements): array
    {
        $properties = [];
        $required = [];

        foreach ($requirements as $requirement) {
            $properties[$requirement->id] = [
                'type' => 'object',
                'required' => ['contentBase64'],
                'properties' => [
                    'contentBase64' => [
                        'type' => 'string',
                        'minLength' => 1,
                        'contentEncoding' => 'base64',
                        'title' => $requirement->name,
                        'description' => 'Base64 encoded attachment content.',
                    ],
                ],
                'additionalProperties' => false,
            ];

            if ($requirement->required) {
                $required[] = $requirement->id;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
            'additionalProperties' => false,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /** @param list<TemplateAttachmentRequirement> $requirements */
    private function hasRequiredAttachmentRequirements(array $requirements): bool
    {
        foreach ($requirements as $requirement) {
            if ($requirement->required) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function dataSchemaForBlock(BlockInstance $block): array
    {
        $blockClass = $this->registry->resolve($block->type);

        if (is_subclass_of($blockClass, HasDynamicData::class)) {
            return $blockClass::dataSchema($block->config);
        }

        return $this->reflector->reflectBlock($blockClass)['data'];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $constants
     * @return array<string, mixed>
     */
    private function withDataLayerAnnotations(array $schema, array $defaults, array $constants): array
    {
        if (($schema['type'] ?? null) === 'array') {
            if ($constants !== []) {
                return [
                    'type' => 'object',
                    'properties' => new stdClass,
                    'additionalProperties' => false,
                ];
            }

            if ($defaults !== []) {
                $schema['default'] = $defaults;
            }

            return $schema;
        }

        if (! isset($schema['properties']) || ! is_array($schema['properties'])) {
            return $schema;
        }

        foreach ($defaults as $key => $value) {
            if (! isset($schema['properties'][$key]) || ! is_array($schema['properties'][$key])) {
                continue;
            }

            $schema['properties'][$key]['default'] = $value;
        }

        foreach (array_keys($constants) as $key) {
            unset($schema['properties'][$key]);
        }

        $required = $schema['required'] ?? [];
        if (is_array($required)) {
            $required = array_values(array_filter(
                $required,
                static fn (mixed $field): bool => is_string($field) && ! array_key_exists($field, $defaults) && ! array_key_exists($field, $constants),
            ));

            if ($required === []) {
                unset($schema['required']);
            } else {
                $schema['required'] = $required;
            }
        }

        if ($schema['properties'] === []) {
            $schema['properties'] = new stdClass;
        } else {
            $schema = $this->withNullableOptionalStrings($schema);
        }

        return $schema;
    }

    /** @param array<string, mixed> $schema */
    private function withNullableOptionalStrings(array $schema): array
    {
        $required = $schema['required'] ?? [];
        $required = is_array($required) ? array_flip($required) : [];

        foreach ($schema['properties'] as $key => $property) {
            if (! is_string($key) || isset($required[$key]) || ! is_array($property) || ($property['type'] ?? null) !== 'string') {
                continue;
            }

            $schema['properties'][$key]['type'] = ['string', 'null'];
        }

        return $schema;
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
        if (($schema['type'] ?? null) === 'array') {
            return false;
        }

        $properties = $schema['properties'] ?? null;

        return $properties === null || $properties instanceof stdClass || $properties === [];
    }

    /** @param array<string, mixed> $schema */
    private function requiresBlockData(array $schema): bool
    {
        if (($schema['type'] ?? null) === 'array') {
            return ! array_key_exists('default', $schema);
        }

        return ($schema['required'] ?? []) !== [];
    }
}
