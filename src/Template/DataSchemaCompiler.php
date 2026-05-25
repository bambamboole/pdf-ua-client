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

        foreach ($template->rows as $row) {
            foreach ($row->blocks as $block) {
                $dataSchema = $this->reflector->reflectBlock($this->registry->resolve($block->type))['data'];

                if ($this->hasNoProperties($dataSchema)) {
                    continue;
                }

                $id = (string) $block->id;
                $properties[$id] = $dataSchema;

                if (isset($dataSchema['required']) && $dataSchema['required'] !== []) {
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

    /** @param array<string, mixed> $schema */
    private function hasNoProperties(array $schema): bool
    {
        $properties = $schema['properties'] ?? null;

        return $properties === null || $properties instanceof stdClass || $properties === [];
    }
}
