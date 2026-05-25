<?php

declare(strict_types=1);
namespace Workbench\App\Http\Controllers;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Exceptions\TemplateValidationException;
use Bambamboole\PdfUaClient\Rendering\RenderOptions;
use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TemplateBuilderController
{
    public function index(TemplateSchemaCompiler $compiler, BlockRegistry $registry): Response
    {
        return Inertia::render('Builder', [
            'schema' => $compiler->compile($registry),
        ]);
    }

    public function render(Request $request, TemplateFactory $factory, TemplateRenderer $renderer): JsonResponse
    {
        /** @var array<string, mixed> $template */
        $template = (array) $request->input('template', []);
        /** @var array<string, array<string, mixed>> $data */
        $data = (array) $request->input('data', []);

        try {
            $built = $factory->fromArray($template);
        } catch (TemplateValidationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $html = $renderer->render($built, $data, new RenderOptions(mode: 'preview', title: 'Preview'));

        return response()->json(['html' => $html]);
    }
}
