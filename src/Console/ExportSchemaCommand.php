<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Console;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ExportSchemaCommand extends Command
{
    /** @var string */
    protected $signature = 'pdf-ua-client:schema-export {path?}';

    /** @var string */
    protected $description = 'Export the compiled JSON Schema for pdf-ua-client templates.';

    public function handle(BlockRegistry $registry, TemplateSchemaCompiler $compiler): int
    {
        /** @var string|null $argPath */
        $argPath = $this->argument('path');
        $path = $argPath ?? storage_path('app/pdf-ua-client/template.schema.json');

        File::ensureDirectoryExists(dirname($path));

        $schema = $compiler->compile($registry);
        File::put($path, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Schema written to: {$path}");

        return self::SUCCESS;
    }
}
