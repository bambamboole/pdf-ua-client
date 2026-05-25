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
use Bambamboole\PdfUaClient\Support\CssRuleEmitter;
use Bambamboole\PdfUaClient\Template\BlockInstance;
use Bambamboole\PdfUaClient\Template\Row;
use Bambamboole\PdfUaClient\Template\Template;

final class TemplateRenderer
{
    private int $blockCounter = 0;

    public function __construct(
        private readonly BlockHydrator $hydrator,
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

        $ctx = new RenderContext;

        $rowsHtml = '';
        foreach ($template->rows as $row) {
            $rowsHtml .= $this->renderRow($row, $runtimeData, $ctx);
        }

        return $this->wrapDocument($rowsHtml, $template, $ctx, $options);
    }

    /**
     * @param  array<string, array<string, mixed>>  $runtimeData
     */
    private function renderRow(Row $row, array $runtimeData, RenderContext $ctx): string
    {
        $cells = '';
        foreach ($row->blocks as $i => $instance) {
            $cellWidth = $row->columnWidths[$i] ?? null;
            $widthOnCell = false;

            if ($cellWidth === null && count($row->blocks) > 1) {
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
        $mergedProps = $this->mergeProps($instance->props, $runtimeData[$instance->id] ?? []);
        $resolvedInstance = new BlockInstance(
            type: $instance->type,
            props: $mergedProps,
            id: $instance->id,
            config: $instance->config,
        );

        $hydrated = $this->hydrator->hydrate($resolvedInstance);
        $config = $hydrated->config;

        $this->blockCounter++;
        $id = "block-{$this->blockCounter}";
        $body = $hydrated->render();

        foreach (CssRuleEmitter::for($config) as $suffix => $props) {
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

    private function emitPositioningCss(RenderContext $ctx, string $id, BlockConfig $config, bool $widthOnCell = false): void
    {
        $positioning = [];

        if (! $widthOnCell && $config->width !== null) {
            $positioning[] = "width: {$config->width}";
        }

        match ($config->align) {
            Align::Center => array_push($positioning, 'margin-left: auto', 'margin-right: auto'),
            Align::Right => $positioning[] = 'margin-left: auto',
            default => null,
        };

        if ($positioning !== []) {
            $ctx->css(".{$id} { ".implode('; ', $positioning).'; }');
        }
    }

    /**
     * @param  array<string, mixed>  $inline
     * @param  array<string, mixed>  $runtime
     * @return array<string, mixed>
     */
    private function mergeProps(array $inline, array $runtime): array
    {
        return array_merge($inline, $runtime);
    }

    private function wrapDocument(string $bodyHtml, Template $template, RenderContext $ctx, RenderOptions $options): string
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

        $bodyTypographyProps = CssRuleEmitter::for($template->config->typography)[''] ?? '';
        $bodyTypographyBlock = $bodyTypographyProps !== '' ? "body { {$bodyTypographyProps} }" : '';
        $baseCss = 'hr { border: none; }';

        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta charset="UTF-8">
<title>{$title}</title>
<style>
{$pageBlock}
{$bodyPadding}
{$bodyTypographyBlock}
{$baseCss}
{$css}
</style>
</head>
<body>
{$bodyHtml}
</body>
</html>
HTML;
    }

    private function printPageCss(PageConfig $page): string
    {
        $margin = $this->marginShorthand($page->margins);
        $css = "@page { size: {$page->format->value}; margin: {$margin}; }";

        if ($page->pageNumbers !== null) {
            $position = $page->pageNumbers->position;
            $css .= " @page { @bottom-{$position} { content: counter(page) \" / \" counter(pages); font-size: 8pt; color: #9ca3af; } }";
        }

        return $css;
    }

    private function marginShorthand(SpacingConfig $margins): string
    {
        $top = $margins->top ?? 0;
        $right = $margins->right ?? 0;
        $bottom = $margins->bottom ?? 0;
        $left = $margins->left ?? 0;

        return "{$top}mm {$right}mm {$bottom}mm {$left}mm";
    }
}
