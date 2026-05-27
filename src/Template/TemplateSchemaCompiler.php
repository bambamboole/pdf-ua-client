<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Config\PageFooterConfig;
use Bambamboole\PdfUaClient\Config\TemplateConfig;
use Bambamboole\PdfUaClient\Enums\AttachmentRelationship;
use ReflectionClass;
use stdClass;

final readonly class TemplateSchemaCompiler
{
    public function __construct(
        private PropsReflector $reflector,
    ) {}

    /** @return array<string, mixed> */
    public function compile(BlockRegistry $registry): array
    {
        $defs = new SchemaRegistry;
        $blockRefs = [];

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
            ],
            'additionalProperties' => false,
        ]);

        $defs->ref('block', ['oneOf' => $blockRefs]);
        $defs->ref('attachment', $this->attachmentSchema());
        $defs->ref('attachmentRequirement', $this->attachmentRequirementSchema());
        $this->registerPageFooterConfig($defs);

        $templateConfigRef = $defs->ref('templateConfig', $this->reflector->reflectWithRefs(TemplateConfig::class, $defs));

        $allDefs = $this->makeConfigsOptional($defs->all());

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
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'example' => $this->dataLayerSchema(),
                        'defaults' => $this->dataLayerSchema(),
                        'constants' => $this->dataLayerSchema(),
                    ],
                    'additionalProperties' => false,
                ],
                'attachments' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/$defs/attachment'],
                ],
                'attachmentRequirements' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/$defs/attachmentRequirement'],
                ],
            ],
            '$defs' => $allDefs === [] ? new stdClass : $allDefs,
        ];

        return $schema;
    }

    /** @return array<string, mixed> */
    private function attachmentSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['name', 'contentBase64', 'mimeType'],
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'title' => 'File name',
                    'description' => 'File name shown in the PDF attachment list.',
                ],
                'contentBase64' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'contentEncoding' => 'base64',
                    'title' => 'Content',
                    'description' => 'Base64 encoded attachment content.',
                ],
                'mimeType' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'title' => 'MIME type',
                    'description' => 'Attachment media type, for example application/xml.',
                ],
                'description' => [
                    'type' => 'string',
                    'title' => 'Description',
                    'description' => 'Optional human-readable attachment description.',
                ],
                'relationship' => [
                    'type' => 'string',
                    'enum' => array_map(static fn (AttachmentRelationship $relationship): string => $relationship->value, AttachmentRelationship::cases()),
                    'title' => 'Relationship',
                    'description' => 'PDF associated-file relationship.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function attachmentRequirementSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['id', 'name', 'mimeType'],
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'title' => 'Attachment id',
                    'description' => 'Runtime data key used to provide this attachment.',
                ],
                'name' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'title' => 'File name',
                    'description' => 'File name shown in the PDF attachment list.',
                ],
                'mimeType' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'title' => 'MIME type',
                    'description' => 'Attachment media type, for example application/xml.',
                ],
                'description' => [
                    'type' => 'string',
                    'title' => 'Description',
                    'description' => 'Optional human-readable attachment description.',
                ],
                'relationship' => [
                    'type' => 'string',
                    'enum' => array_map(static fn (AttachmentRelationship $relationship): string => $relationship->value, AttachmentRelationship::cases()),
                    'title' => 'Relationship',
                    'description' => 'PDF associated-file relationship.',
                ],
                'required' => [
                    'type' => 'boolean',
                    'title' => 'Required',
                    'description' => 'Require this attachment in the runtime data contract.',
                    'default' => true,
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function dataLayerSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => true,
        ];
    }

    private function registerPageFooterConfig(SchemaRegistry $defs): void
    {
        $defs->ref(lcfirst((new ReflectionClass(PageFooterConfig::class))->getShortName()), [
            'type' => 'object',
            'properties' => [
                'repeat' => [
                    'type' => 'boolean',
                    'title' => 'Repeat',
                    'description' => 'Repeat the footer on every rendered page.',
                    'default' => true,
                ],
                'rows' => [
                    'type' => 'array',
                    'title' => 'Rows',
                    'description' => 'Footer rows rendered after the page body.',
                    'items' => ['$ref' => '#/$defs/row'],
                    'default' => [],
                ],
            ],
            'additionalProperties' => false,
        ]);
    }

    /**
     * @param  class-string  $configClass
     * @return array<string, string>
     */
    private function registerConfigRef(string $configClass, SchemaRegistry $defs): array
    {
        $defName = lcfirst(new ReflectionClass($configClass)->getShortName());

        return $defs->ref($defName, $this->reflector->reflectWithRefs($configClass, $defs));
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
     * Config fields are always optional in template JSON. Reflection emits
     * `required` for non-nullable, default-less constructor params, so strip it
     * from every config-named $def (including those registered transitively
     * during inheritance reflection).
     *
     * @param  array<string, array<string, mixed>>  $defs
     * @return array<string, array<string, mixed>>
     */
    private function makeConfigsOptional(array $defs): array
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
