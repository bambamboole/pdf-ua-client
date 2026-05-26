<?php

declare(strict_types=1);
namespace Workbench\App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Workbench\App\Support\ExampleRegistry;

final class ExportExamplesCommand extends Command
{
    /** @var string */
    protected $signature = 'pdf-ua-client:examples-export {path?}';

    /** @var string */
    protected $description = 'Export the registered pdf-ua-client template examples as JSON.';

    public function handle(ExampleRegistry $examples): int
    {
        /** @var string|null $argPath */
        $argPath = $this->argument('path');
        $path = $argPath ?? storage_path('app/pdf-ua-client/examples.json');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($examples->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Examples written to: {$path}");

        return self::SUCCESS;
    }
}
