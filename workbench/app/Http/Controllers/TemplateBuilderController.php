<?php

declare(strict_types=1);
namespace Workbench\App\Http\Controllers;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Exceptions\DataValidationException;
use Bambamboole\PdfUaClient\Exceptions\TemplateValidationException;
use Bambamboole\PdfUaClient\Http\Exceptions\PdfApiException;
use Bambamboole\PdfUaClient\Http\PdfApiClient;
use Bambamboole\PdfUaClient\Rendering\RenderOptions;
use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Bambamboole\PdfUaClient\Template\ExampleRegistry;
use Bambamboole\PdfUaClient\Template\Template;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TemplateBuilderController
{
    public function index(TemplateSchemaCompiler $compiler, BlockRegistry $registry, ExampleRegistry $examples): Response
    {
        return Inertia::render('Builder', [
            'schema' => $compiler->compile($registry),
            'examples' => $examples->all(),
        ]);
    }

    public function html(Request $request, TemplateFactory $factory, TemplateRenderer $renderer): JsonResponse
    {
        try {
            [$built, $data] = $this->buildTemplate($request, $factory);
            $html = $renderer->render($built, $data, new RenderOptions(mode: 'preview', title: 'Preview'));
        } catch (TemplateValidationException|DataValidationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['html' => $html]);
    }

    public function schema(Request $request, TemplateFactory $factory, DataSchemaCompiler $compiler): JsonResponse
    {
        try {
            [$built] = $this->buildTemplate($request, $factory);
            $schema = $compiler->compile($built);
        } catch (TemplateValidationException|DataValidationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['dataSchema' => $schema]);
    }

    public function pdf(Request $request, TemplateFactory $factory, TemplateRenderer $renderer, PdfApiClient $client): mixed
    {
        try {
            [$built, $data] = $this->buildTemplate($request, $factory);
            $html = $renderer->render($built, $data, new RenderOptions(mode: 'print', title: 'Preview'));
            $pdf = $client->convert($html);
        } catch (TemplateValidationException|DataValidationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (PdfApiException $exception) {
            return response()->json(['message' => $exception->getMessage()], 502);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ]);
    }

    /**
     * @return array{0: Template, 1: array<string, array<string, mixed>>}
     */
    private function buildTemplate(Request $request, TemplateFactory $factory): array
    {
        /** @var array<string, mixed> $template */
        $template = (array) $request->input('template', []);
        /** @var array<string, array<string, mixed>> $data */
        $data = (array) $request->input('data', []);

        return [$factory->fromArray($template), $data];
    }
}
