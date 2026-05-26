<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Tests;

use Bambamboole\PdfUaClient\PdfUaClientServiceProvider;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('inertia.pages.paths', [
            dirname(__DIR__).'/workbench/resources/js/Pages',
        ]);
    }

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            PdfUaClientServiceProvider::class,
            WorkbenchServiceProvider::class,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $schema
     * @return list<string>
     */
    public function collectRefs(array $schema): array
    {
        $refs = [];
        $walk = function ($node) use (&$refs, &$walk): void {
            if (! is_array($node)) {
                return;
            }
            if (isset($node['$ref']) && is_string($node['$ref'])) {
                $refs[] = $node['$ref'];
            }
            foreach ($node as $value) {
                if (is_array($value)) {
                    $walk($value);
                }
            }
        };
        $walk($schema);

        return $refs;
    }
}
