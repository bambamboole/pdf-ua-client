<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Console;

use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ExportDataSchemaCommand extends Command
{
    /** @var string */
    protected $signature = 'pdf-ua-client:data-schema-export {template} {path?}';

    /** @var string */
    protected $description = 'Export the data-payload JSON Schema for a given pdf-ua-client template.';

    public function handle(DataSchemaCompiler $compiler): int
    {
        /** @var string $templatePath */
        $templatePath = $this->argument('template');

        if (! File::exists($templatePath)) {
            $this->error("Template file not found: {$templatePath}");

            return self::FAILURE;
        }

        /** @var array<string, mixed> $template */
        $template = json_decode((string) File::get($templatePath), true, flags: JSON_THROW_ON_ERROR);

        /** @var string|null $argPath */
        $argPath = $this->argument('path');
        $path = $argPath ?? storage_path('app/pdf-ua-client/template-data.schema.json');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($compiler->dataSchemaFor($template), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Data schema written to: {$path}");

        return self::SUCCESS;
    }
}
