<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient\Tests;

use Bambamboole\PdfUaClient\PdfUaClientServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PdfUaClientServiceProvider::class];
    }
}
