<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Config\PageConfig;
use Bambamboole\PdfUaClient\Config\PageFooterConfig;
use Bambamboole\PdfUaClient\Config\PageNumbersConfig;
use Bambamboole\PdfUaClient\Config\SpacingConfig;
use Bambamboole\PdfUaClient\Config\TemplateConfig;
use Bambamboole\PdfUaClient\Config\TypographyConfig;
use Bambamboole\PdfUaClient\Enums\AttachmentRelationship;
use Bambamboole\PdfUaClient\Enums\PageFormat;
use Bambamboole\PdfUaClient\Exceptions\TemplateValidationException;
use Bambamboole\PdfUaClient\Support\SchemaAwareNormalizer;
use Bambamboole\PdfUaClient\Support\ValueObjectHydrator;
use Opis\JsonSchema\Validator;

final readonly class TemplateFactory
{
    public function __construct(
        private BlockRegistry $registry,
        private TemplateSchemaCompiler $compiler,
        private ValueObjectHydrator $valueObjectHydrator = new ValueObjectHydrator,
    ) {}

    /** @param array<string, mixed> $data */
    public function fromArray(array $data): Template
    {
        $schema = $this->compiler->compile($this->registry);
        $normalized = SchemaAwareNormalizer::normalize($data, $schema);
        $schemaObject = json_decode((string) json_encode($schema));

        $validator = new Validator;
        $result = $validator->validate($normalized, $schemaObject);

        if (! $result->isValid()) {
            throw new TemplateValidationException(
                'Template failed schema validation',
                $result->error(),
            );
        }

        return new Template(
            version: (int) $data['version'],
            config: $this->buildTemplateConfig((array) ($data['config'] ?? [])),
            rows: $this->buildRows($data['rows']),
            data: $this->buildTemplateData((array) ($data['data'] ?? [])),
            attachments: $this->buildAttachments((array) ($data['attachments'] ?? [])),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     * @return list<TemplateAttachment>
     */
    private function buildAttachments(array $attachments): array
    {
        return array_map(
            fn (array $attachment): TemplateAttachment => new TemplateAttachment(
                name: (string) $attachment['name'],
                contentBase64: (string) $attachment['contentBase64'],
                mimeType: (string) $attachment['mimeType'],
                description: isset($attachment['description']) ? (string) $attachment['description'] : null,
                relationship: isset($attachment['relationship'])
                    ? AttachmentRelationship::from((string) $attachment['relationship'])
                    : null,
            ),
            $attachments,
        );
    }

    /** @param array<string, mixed> $data */
    private function buildTemplateData(array $data): TemplateData
    {
        return new TemplateData(
            example: $this->blockDataMap((array) ($data['example'] ?? [])),
            defaults: $this->blockDataMap((array) ($data['defaults'] ?? [])),
            constants: $this->blockDataMap((array) ($data['constants'] ?? [])),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function blockDataMap(array $data): array
    {
        $map = [];

        foreach ($data as $id => $values) {
            if (! is_array($values)) {
                continue;
            }

            $map[(string) $id] = $values;
        }

        return $map;
    }

    /** @param array<string, mixed> $data */
    private function buildTemplateConfig(array $data): TemplateConfig
    {
        return new TemplateConfig(
            page: $this->buildPageConfig((array) ($data['page'] ?? [])),
            typography: $this->valueObjectHydrator->hydrate(TypographyConfig::class, (array) ($data['typography'] ?? [])),
        );
    }

    /** @param array<string, mixed> $data */
    private function buildPageConfig(array $data): PageConfig
    {
        $margins = isset($data['margins'])
            ? $this->valueObjectHydrator->hydrate(SpacingConfig::class, (array) $data['margins'])
            : new SpacingConfig(20, 20, 20, 25);

        $pageNumbers = isset($data['pageNumbers'])
            ? $this->valueObjectHydrator->hydrate(PageNumbersConfig::class, (array) $data['pageNumbers'])
            : new PageNumbersConfig;

        $footer = isset($data['footer'])
            ? $this->buildPageFooterConfig((array) $data['footer'])
            : new PageFooterConfig;

        $format = isset($data['format'])
            ? PageFormat::from((string) $data['format'])
            : PageFormat::A4;

        return new PageConfig(
            format: $format,
            locale: (string) ($data['locale'] ?? 'de_DE'),
            margins: $margins,
            pageNumbers: $pageNumbers,
            footer: $footer,
        );
    }

    /** @param array<string, mixed> $data */
    private function buildPageFooterConfig(array $data): PageFooterConfig
    {
        return new PageFooterConfig(
            repeat: isset($data['repeat']) ? (bool) $data['repeat'] : true,
            rows: isset($data['rows']) ? $this->buildRows((array) $data['rows'], 'f') : [],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rowsData
     * @return list<Row>
     */
    private function buildRows(array $rowsData, string $idPrefix = 'r'): array
    {
        $rows = [];

        foreach ($rowsData as $rowIndex => $rowData) {
            $blocks = [];

            foreach ($rowData['blocks'] as $blockIndex => $blockData) {
                $blocks[] = new BlockInstance(
                    type: (string) $blockData['type'],
                    id: $blockData['id'] ?? "{$idPrefix}{$rowIndex}b{$blockIndex}",
                    config: (array) ($blockData['config'] ?? []),
                );
            }

            $rows[] = new Row(
                blocks: $blocks,
                gap: $rowData['gap'] ?? null,
            );
        }

        return $rows;
    }
}
