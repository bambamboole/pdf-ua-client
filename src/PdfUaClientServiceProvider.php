<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

use Bambamboole\PdfUaClient\Contracts\PdfClient;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class PdfUaClientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('pdf-ua-client')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->bind(PdfClient::class, PdfUaClient::class);
    }
}
