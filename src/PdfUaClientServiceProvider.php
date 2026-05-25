<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient;

use Bambamboole\PdfUaClient\Block\BlockHydrator;
use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Blocks\DividerBlock;
use Bambamboole\PdfUaClient\Blocks\HeadingBlock;
use Bambamboole\PdfUaClient\Blocks\HtmlBlock;
use Bambamboole\PdfUaClient\Blocks\ImageBlock;
use Bambamboole\PdfUaClient\Blocks\KeyValueBlock;
use Bambamboole\PdfUaClient\Blocks\SpacerBlock;
use Bambamboole\PdfUaClient\Blocks\TableBlock;
use Bambamboole\PdfUaClient\Blocks\TextBlock;
use Bambamboole\PdfUaClient\Console\ExportSchemaCommand;
use Bambamboole\PdfUaClient\Http\PdfApiClient;
use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as HttpFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class PdfUaClientServiceProvider extends PackageServiceProvider
{
    public static string $name = 'pdf-ua-client';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews('pdf-ua-client')
            ->hasTranslations()
            ->hasCommand(ExportSchemaCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PropsReflector::class);

        $this->app->singleton(BlockRegistry::class, function (): BlockRegistry {
            $registry = new BlockRegistry;
            foreach ([
                HeadingBlock::class,
                TextBlock::class,
                HtmlBlock::class,
                ImageBlock::class,
                SpacerBlock::class,
                DividerBlock::class,
                KeyValueBlock::class,
                TableBlock::class,
            ] as $blockClass) {
                $registry->register($blockClass);
            }

            return $registry;
        });

        $this->app->singleton(BlockHydrator::class, fn (Container $app): BlockHydrator => new BlockHydrator(
            $app->make(BlockRegistry::class),
            $app->make(PropsReflector::class),
        ));

        $this->app->singleton(TemplateSchemaCompiler::class, fn (Container $app): TemplateSchemaCompiler => new TemplateSchemaCompiler(
            $app->make(PropsReflector::class),
        ));

        $this->app->singleton(TemplateFactory::class, fn (Container $app): TemplateFactory => new TemplateFactory(
            $app->make(BlockRegistry::class),
            $app->make(TemplateSchemaCompiler::class),
        ));

        $this->app->singleton(TemplateRenderer::class, fn (Container $app): TemplateRenderer => new TemplateRenderer(
            $app->make(BlockHydrator::class),
        ));

        $this->app->singleton(PdfApiClient::class, function (Container $app): PdfApiClient {
            $config = $app->make('config');

            return new PdfApiClient(
                http: $app->make(HttpFactory::class),
                baseUrl: (string) $config->get('pdf-ua-client.base_url'),
                bearerToken: $config->get('pdf-ua-client.token'),
                timeoutSeconds: (int) $config->get('pdf-ua-client.timeout', 30),
                retryAttempts: (int) $config->get('pdf-ua-client.retry.attempts', 2),
                retrySleepMs: (int) $config->get('pdf-ua-client.retry.sleep_ms', 100),
            );
        });
    }
}
