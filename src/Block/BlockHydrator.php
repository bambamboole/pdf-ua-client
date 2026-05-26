<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Block;

use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;
use Bambamboole\PdfUaClient\Exceptions\BlockHydrationException;
use Bambamboole\PdfUaClient\Support\ValueObjectHydrator;
use Bambamboole\PdfUaClient\Template\BlockInstance;
use Throwable;

final readonly class BlockHydrator
{
    public function __construct(
        private BlockRegistry $registry,
        private ValueObjectHydrator $valueObjectHydrator = new ValueObjectHydrator,
    ) {}

    public function hydrate(BlockInstance $instance): HydratedBlock
    {
        $blockClass = $this->registry->resolve($instance->type);
        $configClass = $this->registry->configClass($instance->type);

        try {
            /** @var BlockInterface $block */
            $block = $this->valueObjectHydrator->hydrate($blockClass, $instance->props, requireProvided: true);
            /** @var BlockConfig $config */
            $config = $this->valueObjectHydrator->hydrate($configClass, $instance->config);

            return new HydratedBlock($block, $config);
        } catch (Throwable $e) {
            throw BlockHydrationException::forBlock($blockClass, $e->getMessage(), $e);
        }
    }
}
