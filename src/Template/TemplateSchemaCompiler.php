<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Config\TemplateConfig;
use ReflectionClass;
use stdClass;

final readonly class TemplateSchemaCompiler
{
    public function __construct(
        private PropsReflector $reflector,
        private ExampleRegistry $examples,
    ) {}

    /** @return array<string, mixed> */
    public function compile(BlockRegistry $registry): array
    {
        $defs = new SchemaRegistry;
        $blockRefs = [];

        $templateConfigSchema = $this->reflector->reflectWithRefs(TemplateConfig::class, $defs);
        $templateConfigSchema = $this->stripRequired($templateConfigSchema);
        $templateConfigRef = $defs->ref('templateConfig', $templateConfigSchema);

        $defs->ref('blockBase', [
            'type' => 'object',
            'required' => ['type'],
            'properties' => [
                'type' => ['type' => 'string'],
                'id' => ['type' => 'string'],
            ],
        ]);

        foreach ($registry->all() as $type => $blockClass) {
            $defName = $this->blockDefName($type);
            $propsDefName = $this->blockPropsDefName($type);
            $configClass = $registry->configClass($type);

            $propsSchema = $this->reflector->reflectBlock($blockClass)['data'];

            $configRef = $this->registerConfigRef($configClass, $defs);

            $defs->ref($propsDefName, $propsSchema);

            $defs->ref($defName, [
                'allOf' => [['$ref' => '#/$defs/blockBase']],
                'properties' => [
                    'type' => ['const' => $type, 'type' => 'string'],
                    'config' => $configRef,
                ],
                'unevaluatedProperties' => false,
            ]);

            $blockRefs[] = ['$ref' => "#/\$defs/{$defName}"];
        }

        $defs->ref('row', [
            'type' => 'object',
            'required' => ['blocks'],
            'properties' => [
                'blocks' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => ['$ref' => '#/$defs/block'],
                ],
                'gap' => ['type' => 'integer', 'minimum' => 0],
                'columnWidths' => [
                    'type' => 'array',
                    'items' => ['type' => ['integer', 'string']],
                ],
            ],
            'additionalProperties' => false,
        ]);

        $defs->ref('block', ['oneOf' => $blockRefs]);

        $allDefs = $this->stripRequiredFromConfigs($defs->all());

        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$id' => 'https://pdfuakit.com/schemas/pdf-ua-client-template-v1.json',
            'type' => 'object',
            'required' => ['version', 'config', 'rows'],
            'properties' => [
                'version' => ['const' => 1, 'type' => 'integer'],
                'config' => $templateConfigRef,
                'rows' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/$defs/row'],
                ],
            ],
            '$defs' => $allDefs === [] ? new stdClass : $allDefs,
        ];

        $examples = array_map(static fn (array $entry): array => $entry['template'], $this->examples->all());
        if ($examples !== []) {
            $schema['examples'] = $examples;
        }

        return $schema;
    }

    /**
     * @param  class-string  $configClass
     * @return array<string, string>
     */
    private function registerConfigRef(string $configClass, SchemaRegistry $defs): array
    {
        $defName = lcfirst(new ReflectionClass($configClass)->getShortName());
        $schema = $this->reflector->reflectWithRefs($configClass, $defs);
        $schema = $this->stripRequired($schema);

        return $defs->ref($defName, $schema);
    }

    private function blockDefName(string $type): string
    {
        return $this->camelCase($type).'Block';
    }

    private function blockPropsDefName(string $type): string
    {
        return $this->camelCase($type).'Props';
    }

    private function camelCase(string $type): string
    {
        $parts = preg_split('/[-_]/', $type) ?: [$type];
        $head = lcfirst($parts[0]);
        $tail = array_map(ucfirst(...), array_slice($parts, 1));

        return $head.implode('', $tail);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function stripRequired(array $schema): array
    {
        unset($schema['required']);

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $name => $property) {
                if (is_array($property)) {
                    $schema['properties'][$name] = $this->stripRequired($property);
                }
            }
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->stripRequired($schema['items']);
        }

        return $schema;
    }

    /**
     * Defensive pass: parent configs registered transitively during inheritance
     * reflection bypass the per-child stripRequired call. Strip any lingering
     * `required` entries from every config-named $def so config fields stay
     * optional in template JSON.
     *
     * @param  array<string, array<string, mixed>>  $defs
     * @return array<string, array<string, mixed>>
     */
    private function stripRequiredFromConfigs(array $defs): array
    {
        foreach ($defs as $name => $schema) {
            if (! str_ends_with($name, 'Config')) {
                continue;
            }

            $defs[$name] = $this->stripRequired($schema);
        }

        return $defs;
    }
}
