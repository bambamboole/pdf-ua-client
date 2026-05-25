<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Config\PageConfig;
use Bambamboole\PdfUaClient\Config\PageNumbersConfig;
use Bambamboole\PdfUaClient\Config\SpacingConfig;
use Bambamboole\PdfUaClient\Config\TemplateConfig;
use Bambamboole\PdfUaClient\Config\TypographyConfig;
use Bambamboole\PdfUaClient\Enums\FontWeight;
use Bambamboole\PdfUaClient\Enums\PageFormat;
use Bambamboole\PdfUaClient\Enums\PageNumberPosition;
use Bambamboole\PdfUaClient\Exceptions\TemplateValidationException;
use Bambamboole\PdfUaClient\Support\SchemaAwareNormalizer;
use Opis\JsonSchema\Validator;

final readonly class TemplateFactory
{
    public function __construct(
        private BlockRegistry $registry,
        private TemplateSchemaCompiler $compiler,
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
        );
    }

    /** @param array<string, mixed> $data */
    private function buildTemplateConfig(array $data): TemplateConfig
    {
        return new TemplateConfig(
            page: $this->buildPageConfig((array) ($data['page'] ?? [])),
            typography: $this->buildTypographyConfig((array) ($data['typography'] ?? [])),
        );
    }

    /** @param array<string, mixed> $data */
    private function buildPageConfig(array $data): PageConfig
    {
        $margins = isset($data['margins'])
            ? $this->buildSpacingConfig((array) $data['margins'])
            : new SpacingConfig(20, 20, 20, 25);

        $pageNumbers = isset($data['pageNumbers'])
            ? $this->buildPageNumbersConfig((array) $data['pageNumbers'])
            : new PageNumbersConfig;

        $format = isset($data['format'])
            ? PageFormat::from((string) $data['format'])
            : PageFormat::A4;

        return new PageConfig(
            format: $format,
            locale: (string) ($data['locale'] ?? 'de_DE'),
            margins: $margins,
            pageNumbers: $pageNumbers,
        );
    }

    /** @param array<string, mixed> $data */
    private function buildSpacingConfig(array $data): SpacingConfig
    {
        return new SpacingConfig(
            top: isset($data['top']) ? (int) $data['top'] : null,
            right: isset($data['right']) ? (int) $data['right'] : null,
            bottom: isset($data['bottom']) ? (int) $data['bottom'] : null,
            left: isset($data['left']) ? (int) $data['left'] : null,
        );
    }

    /** @param array<string, mixed> $data */
    private function buildPageNumbersConfig(array $data): PageNumbersConfig
    {
        return new PageNumbersConfig(
            enabled: isset($data['enabled']) ? (bool) $data['enabled'] : true,
            position: PageNumberPosition::from((string) ($data['position'] ?? PageNumberPosition::Center->value)),
        );
    }

    /** @param array<string, mixed> $data */
    private function buildTypographyConfig(array $data): TypographyConfig
    {
        $weight = null;
        if (isset($data['weight'])) {
            $weight = FontWeight::from((int) $data['weight']);
        }

        return new TypographyConfig(
            family: isset($data['family']) ? (string) $data['family'] : null,
            size: isset($data['size']) ? (int) $data['size'] : null,
            weight: $weight,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rowsData
     * @return list<Row>
     */
    private function buildRows(array $rowsData): array
    {
        $rows = [];

        foreach ($rowsData as $rowIndex => $rowData) {
            $blocks = [];

            foreach ($rowData['blocks'] as $blockIndex => $blockData) {
                $blocks[] = new BlockInstance(
                    type: (string) $blockData['type'],
                    id: $blockData['id'] ?? "r{$rowIndex}b{$blockIndex}",
                    config: (array) ($blockData['config'] ?? []),
                );
            }

            $rows[] = new Row(
                blocks: $blocks,
                gap: $rowData['gap'] ?? null,
                columnWidths: $rowData['columnWidths'] ?? null,
            );
        }

        return $rows;
    }
}
