<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Block;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;
use Bambamboole\PdfUaClient\Exceptions\BlockTypeNotRegisteredException;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;

final class BlockRegistry
{
    /** @var array<string, class-string<BlockInterface>> */
    private array $blocks = [];

    /** @var array<string, class-string<BlockConfig>> */
    private array $configs = [];

    /** @param class-string<BlockInterface> $blockClass */
    public function register(string $blockClass): void
    {
        $reflection = new ReflectionClass($blockClass);

        $attributes = $reflection->getAttributes(Block::class);

        if ($attributes === []) {
            throw new InvalidArgumentException("{$blockClass} must declare #[Block(...)] attribute");
        }

        if (! $reflection->implementsInterface(BlockInterface::class)) {
            throw new InvalidArgumentException("{$blockClass} must implement ".BlockInterface::class);
        }

        $attribute = $attributes[0]->newInstance();
        $configClass = $attribute->config;

        if (! $reflection->hasMethod('render')) {
            throw new InvalidArgumentException("{$blockClass} must define a render(...) method");
        }

        $renderMethod = $reflection->getMethod('render');

        if (! $renderMethod->isPublic()) {
            throw new InvalidArgumentException("{$blockClass}::render() must be public");
        }

        $params = $renderMethod->getParameters();
        if (count($params) !== 1) {
            throw new InvalidArgumentException("{$blockClass}::render() must accept exactly one parameter (the config)");
        }

        $paramType = $params[0]->getType();
        if (! $paramType instanceof ReflectionNamedType) {
            throw new InvalidArgumentException("{$blockClass}::render() parameter must declare a single typed config class");
        }

        $paramTypeName = $paramType->getName();
        if ($paramTypeName !== $configClass) {
            throw new InvalidArgumentException(
                "{$blockClass}::render() parameter type ({$paramTypeName}) must match the #[Block(config:)] class ({$configClass})"
            );
        }

        $this->blocks[$attribute->type] = $blockClass;
        $this->configs[$attribute->type] = $configClass;
    }

    /** @return class-string<BlockInterface> */
    public function resolve(string $type): string
    {
        if (! isset($this->blocks[$type])) {
            throw BlockTypeNotRegisteredException::forType($type);
        }

        return $this->blocks[$type];
    }

    /** @return class-string<BlockConfig> */
    public function configClass(string $type): string
    {
        if (! isset($this->configs[$type])) {
            throw BlockTypeNotRegisteredException::forType($type);
        }

        return $this->configs[$type];
    }

    /** @return array<string, class-string<BlockInterface>> */
    public function all(): array
    {
        return $this->blocks;
    }
}
