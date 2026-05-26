<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Rendering;

use Bambamboole\PdfUaClient\Block\BlockHydrator;
use Bambamboole\PdfUaClient\Block\RenderContext;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Config\PageConfig;
use Bambamboole\PdfUaClient\Config\SpacingConfig;
use Bambamboole\PdfUaClient\Contracts\EmitsCss;
use Bambamboole\PdfUaClient\Enums\Align;
use Bambamboole\PdfUaClient\Exceptions\DataValidationException;
use Bambamboole\PdfUaClient\Fonts\FontDefinition;
use Bambamboole\PdfUaClient\Fonts\FontRegistry;
use Bambamboole\PdfUaClient\Support\CssRuleEmitter;
use Bambamboole\PdfUaClient\Support\SchemaAwareNormalizer;
use Bambamboole\PdfUaClient\Template\BlockInstance;
use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Bambamboole\PdfUaClient\Template\Row;
use Bambamboole\PdfUaClient\Template\Template;
use Opis\JsonSchema\Validator;

final class TemplateRenderer
{
    private const int RepeatedFooterReserveMm = 8;

    private int $blockCounter = 0;

    /** @var array<string, true> */
    private array $usedFontKeys = [];

    public function __construct(
        private readonly BlockHydrator $hydrator,
        private readonly DataSchemaCompiler $dataSchemaCompiler,
        private readonly ?FontRegistry $fonts = null,
    ) {}

    /**
     * @param  array<string, array<string, mixed>>  $runtimeData
     */
    public function render(
        Template $template,
        array $runtimeData = [],
        RenderOptions $options = new RenderOptions,
    ): string {
        $this->blockCounter = 0;
        $this->usedFontKeys = [];

        $this->validateData($template, $runtimeData);

        $ctx = new RenderContext;

        $rowsHtml = '';
        foreach ($template->rows as $row) {
            $rowsHtml .= $this->renderRow($row, $runtimeData, $ctx);
        }

        $footerHtml = $this->renderFooter($template, $runtimeData, $ctx, $options);

        return $this->wrapDocument($rowsHtml, $footerHtml, $template, $ctx, $options);
    }

    /**
     * @param  array<string, array<string, mixed>>  $runtimeData
     */
    private function renderRow(Row $row, array $runtimeData, RenderContext $ctx): string
    {
        $cells = '';
        foreach ($row->blocks as $i => $instance) {
            $cellWidth = null;
            $widthOnCell = false;

            if (count($row->blocks) > 1) {
                $configWidth = $instance->config['width'] ?? null;
                if ($configWidth !== null) {
                    $cellWidth = $configWidth;
                    $widthOnCell = true;
                }
            }

            $widthAttr = $cellWidth !== null ? ' style="width: '.htmlspecialchars((string) $cellWidth, ENT_QUOTES).';"' : '';
            $cells .= "<td{$widthAttr}>{$this->renderBlock($instance, $runtimeData, $ctx, $widthOnCell)}</td>";
        }

        return "<table class=\"row\" role=\"presentation\"><tr>{$cells}</tr></table>";
    }

    /**
     * @param  array<string, array<string, mixed>>  $runtimeData
     */
    private function renderBlock(BlockInstance $instance, array $runtimeData, RenderContext $ctx, bool $widthOnCell = false): string
    {
        $resolvedInstance = new BlockInstance(
            type: $instance->type,
            props: $runtimeData[$instance->id] ?? [],
            id: $instance->id,
            config: $instance->config,
        );

        $hydrated = $this->hydrator->hydrate($resolvedInstance);
        $config = $hydrated->config;

        $this->blockCounter++;
        $id = "block-{$this->blockCounter}";
        $body = $hydrated->render();

        foreach ($this->cssRulesFor($config) as $suffix => $props) {
            $selector = ".{$id}".($suffix !== '' ? " {$suffix}" : '');
            $ctx->css("{$selector} { {$props} }");
        }

        $this->emitPositioningCss($ctx, $id, $config, $widthOnCell);

        if ($config instanceof EmitsCss) {
            $extra = $config->cssRules($id);
            if ($extra !== '') {
                $ctx->css($extra);
            }
        }

        return "<div class=\"{$id}\">{$body}</div>";
    }

    /**
     * @param  array<string, array<string, mixed>>  $runtimeData
     */
    private function renderFooter(Template $template, array $runtimeData, RenderContext $ctx, RenderOptions $options): string
    {
        $footer = $template->config->page->footer;

        if ($footer->rows === []) {
            return '';
        }

        $rowsHtml = '';
        foreach ($footer->rows as $row) {
            $rowsHtml .= $this->renderRow($row, $runtimeData, $ctx);
        }

        $class = $options->mode === 'print' && $footer->repeat
            ? 'page-footer page-footer-repeated'
            : 'page-footer page-footer-preview';

        return "<footer class=\"{$class}\" role=\"contentinfo\">{$rowsHtml}</footer>";
    }

    private function emitPositioningCss(RenderContext $ctx, string $id, BlockConfig $config, bool $widthOnCell = false): void
    {
        $positioning = [];

        if (! $widthOnCell && $config->width !== null) {
            $positioning[] = "width: {$config->width}";
        }

        match ($config->align) {
            Align::Center => array_push($positioning, 'margin-left: auto', 'margin-right: auto'),
            Align::Right => array_push($positioning, 'margin-left: auto', 'text-align: right'),
            default => null,
        };

        if ($positioning !== []) {
            $ctx->css(".{$id} { ".implode('; ', $positioning).'; }');
        }
    }

    /** @param array<string, array<string, mixed>> $runtimeData */
    private function validateData(Template $template, array $runtimeData): void
    {
        $schema = $this->dataSchemaCompiler->compile($template);
        $runtimeData = $this->withoutEmptyDataForBlocksWithoutDataSchema($template, $runtimeData, $schema);
        $normalized = SchemaAwareNormalizer::normalize($runtimeData, $schema);
        $validator = new Validator;
        $result = $validator->validate($normalized, json_decode((string) json_encode($schema)));

        if (! $result->isValid()) {
            throw new DataValidationException('Data failed schema validation', $result->error());
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $runtimeData
     * @param  array<string, mixed>  $schema
     * @return array<string, array<string, mixed>>
     */
    private function withoutEmptyDataForBlocksWithoutDataSchema(Template $template, array $runtimeData, array $schema): array
    {
        $schemaProperties = $schema['properties'];
        $dataBlockIds = is_array($schemaProperties) ? array_keys($schemaProperties) : [];
        $dataBlockIdLookup = array_fill_keys($dataBlockIds, true);

        foreach ($this->dataRows($template) as $row) {
            foreach ($row->blocks as $block) {
                $id = (string) $block->id;

                if (isset($dataBlockIdLookup[$id]) || ($runtimeData[$id] ?? null) !== []) {
                    continue;
                }

                unset($runtimeData[$id]);
            }
        }

        return $runtimeData;
    }

    /** @return list<Row> */
    private function dataRows(Template $template): array
    {
        return [
            ...$template->rows,
            ...$template->config->page->footer->rows,
        ];
    }

    private function wrapDocument(string $bodyHtml, string $footerHtml, Template $template, RenderContext $ctx, RenderOptions $options): string
    {
        $page = $template->config->page;
        $css = $ctx->collectedCss();
        $title = htmlspecialchars($options->title, ENT_QUOTES);
        $lang = explode('_', $page->locale)[0];

        $pageBlock = $options->mode === 'print'
            ? $this->printPageCss($page)
            : '';

        $bodyPadding = $options->mode === 'preview'
            ? 'body { padding: '.$this->marginShorthand($page->margins).'; }'
            : '';

        $bodyTypographyProps = $this->cssRulesFor($template->config->typography)[''] ?? '';
        $bodyTypographyBlock = $bodyTypographyProps !== '' ? "body { {$bodyTypographyProps} }" : '';
        $fontFaces = $this->fontFaceCss();
        $baseCss = $this->baseCss();
        $pageMarksCss = $options->mode === 'print'
            ? $this->pageMarksCss($page)
            : '';
        $pageMarksHtml = $options->mode === 'print'
            ? $this->renderPageMarks($page)
            : '';
        $hasRepeatedFooter = $options->mode === 'print' && $page->footer->repeat && $page->footer->rows !== [];
        $bodyContent = $hasRepeatedFooter
            ? $pageMarksHtml.$footerHtml."\n".$bodyHtml
            : $pageMarksHtml.$bodyHtml."\n".$footerHtml;

        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta charset="UTF-8">
<title>{$title}</title>
<style>
{$pageBlock}
{$bodyPadding}
{$fontFaces}
{$bodyTypographyBlock}
{$baseCss}
{$pageMarksCss}
{$css}
</style>
</head>
<body>
{$bodyContent}
</body>
</html>
HTML;
    }

    private function renderPageMarks(PageConfig $page): string
    {
        $marks = [];

        if ($page->foldMarks) {
            $marks[] = '<span class="page-mark page-mark-fold-top"></span>';
            $marks[] = '<span class="page-mark page-mark-fold-bottom"></span>';
        }

        if ($page->punchMarks) {
            $marks[] = '<span class="page-mark page-mark-punch"></span>';
        }

        if ($marks === []) {
            return '';
        }

        return '<div class="page-marks" aria-hidden="true">'.implode('', $marks)."</div>\n";
    }

    private function pageMarksCss(PageConfig $page): string
    {
        if (! $page->foldMarks && ! $page->punchMarks) {
            return '';
        }

        return <<<'CSS'
.page-marks { position: absolute; width: 0; height: 0; overflow: visible; }
.page-mark { display: block; width: 5mm; border-top: 0.2mm solid #9ca3af; }
.page-mark-fold-top { position: running(pageMarkFoldTop); margin-top: 67mm; }
.page-mark-fold-bottom { position: running(pageMarkFoldBottom); margin-bottom: 85mm; }
.page-mark-punch { position: running(pageMarkPunch); }
CSS;
    }

    private function baseCss(): string
    {
        return <<<'CSS'
body { color: #111827; line-height: 1.35; }
p { margin: 0 0 2mm; }
h1, h2, h3, h4, h5, h6 { margin: 0 0 3mm; line-height: 1.12; color: #111827; }
hr { border: none; border-top: 1px solid #d1d5db; margin: 2.5mm 0; }
.row { width: 100%; border-collapse: collapse; margin: 0 0 3mm; }
.row > tbody > tr > td, .row > tr > td { vertical-align: top; padding: 0; }
.key-value { display: inline-table; border-collapse: collapse; text-align: left; }
.key-value td { padding: 0.65mm 0 0.65mm 3mm; vertical-align: top; }
.key-value td:first-child { padding-left: 0; color: #6b7280; font-weight: 600; }
.key-value td:last-child { color: #111827; font-weight: 500; }
.data-table { width: 100%; border-collapse: collapse; text-align: left; -fs-table-paginate: paginate; }
.data-table th { padding: 1.6mm 2.2mm; background: #f3f4f6; color: #374151; font-weight: 700; border-bottom: 1px solid #d1d5db; }
.data-table td { padding: 1.6mm 2.2mm; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
.data-table tbody tr:last-child td { border-bottom: 1px solid #d1d5db; }
.page-footer { color: #6b7280; font-size: 8pt; line-height: 1.25; }
.page-footer .row { margin: 0; }
.page-footer-repeated { position: running(pageFooter); width: 100%; }
.page-footer-repeated .row > tbody > tr > td:first-child, .page-footer-repeated .row > tr > td:first-child { padding-left: 2.2mm; }
.page-footer-repeated .row > tbody > tr > td:last-child, .page-footer-repeated .row > tr > td:last-child { padding-right: 2.2mm; }
.page-footer-preview { margin-top: 6mm; padding-top: 2mm; border-top: 1px solid #d1d5db; }
CSS;
    }

    private function printPageCss(PageConfig $page): string
    {
        $margin = $this->printMarginShorthand($page);
        $css = "@page { size: {$page->format->value}; margin: {$margin}; }";

        if ($page->footer->repeat && $page->footer->rows !== []) {
            $css .= ' @page { @bottom-center { content: element(pageFooter); } }';
        }

        if ($page->foldMarks) {
            $css .= ' @page { @left-top { content: element(pageMarkFoldTop); width: 25mm; } @left-bottom { content: element(pageMarkFoldBottom); width: 25mm; } }';
        }

        if ($page->punchMarks) {
            $css .= ' @page { @left-middle { content: element(pageMarkPunch); width: 25mm; } }';
        }

        if ($page->pageNumbers->enabled) {
            $position = $page->pageNumbers->position->value;
            $css .= " @page { @bottom-{$position} { content: counter(page) \" / \" counter(pages); font-size: 8pt; color: #9ca3af; vertical-align: bottom; padding-bottom: 4mm; } }";
        }

        return $css;
    }

    /** @return array<string, string> */
    private function cssRulesFor(?object $config): array
    {
        $rules = CssRuleEmitter::for($config);

        foreach ($rules as $suffix => $props) {
            $rules[$suffix] = $this->resolveRegisteredFontFamilies($props);
        }

        return $rules;
    }

    private function resolveRegisteredFontFamilies(string $props): string
    {
        if ($this->fonts === null) {
            return $props;
        }

        return (string) preg_replace_callback(
            "/font-family: '([^']+)';/",
            function (array $matches): string {
                $key = $matches[1];
                $font = $this->fonts->get($key);

                if ($font === null) {
                    return $matches[0];
                }

                $this->usedFontKeys[$key] = true;

                return "font-family: '{$this->cssString($font->family)}';";
            },
            $props,
        );
    }

    private function fontFaceCss(): string
    {
        if ($this->fonts === null || $this->usedFontKeys === []) {
            return '';
        }

        $rules = [];
        foreach (array_keys($this->usedFontKeys) as $key) {
            $font = $this->fonts->get($key);
            if ($font === null || $font->url === null) {
                continue;
            }

            $rules[] = $this->fontFaceRule($font);
        }

        return implode("\n", $rules);
    }

    private function fontFaceRule(FontDefinition $font): string
    {
        $declarations = [
            "font-family: '{$this->cssString($font->family)}'",
            "src: url(\"{$this->cssUrl($font->url)}\") format(\"{$this->cssString($font->format)}\")",
        ];

        if ($font->weight !== null) {
            $declarations[] = "font-weight: {$font->weight}";
        }

        $declarations[] = "font-style: {$font->style}";
        $declarations[] = "font-display: {$font->display}";

        return '@font-face { '.implode('; ', $declarations).'; }';
    }

    private function cssString(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }

    private function cssUrl(string $value): string
    {
        return str_replace(['\\', '"', ')'], ['\\\\', '\"', '\)'], $value);
    }

    private function marginShorthand(SpacingConfig $margins): string
    {
        $top = $margins->top ?? 0;
        $right = $margins->right ?? 0;
        $bottom = $margins->bottom ?? 0;
        $left = $margins->left ?? 0;

        return "{$top}mm {$right}mm {$bottom}mm {$left}mm";
    }

    private function printMarginShorthand(PageConfig $page): string
    {
        $top = $page->margins->top ?? 0;
        $right = $page->margins->right ?? 0;
        $bottom = ($page->margins->bottom ?? 0) + $this->repeatedFooterReserve($page);
        $left = $page->margins->left ?? 0;

        return "{$top}mm {$right}mm {$bottom}mm {$left}mm";
    }

    private function repeatedFooterReserve(PageConfig $page): int
    {
        if (! $page->footer->repeat || $page->footer->rows === []) {
            return 0;
        }

        return self::RepeatedFooterReserveMm;
    }
}
