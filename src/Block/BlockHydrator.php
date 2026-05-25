<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Block;

use BackedEnum;
use Bambamboole\PdfUaClient\Attributes\ArrayOf;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;
use Bambamboole\PdfUaClient\Exceptions\BlockHydrationException;
use Bambamboole\PdfUaClient\Template\BlockInstance;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

final readonly class BlockHydrator
{
    public function __construct(
        private BlockRegistry $registry,
    ) {}

    public function hydrate(BlockInstance $instance): HydratedBlock
    {
        $blockClass = $this->registry->resolve($instance->type);
        $configClass = $this->registry->configClass($instance->type);

        try {
            $block = $this->instantiateBlock($blockClass, $instance);
            /** @var BlockConfig $config */
            $config = $this->instantiateValueObject($configClass, $instance->config);

            return new HydratedBlock($block, $config);
        } catch (Throwable $e) {
            throw BlockHydrationException::forBlock($blockClass, $e->getMessage(), $e);
        }
    }

    /** @param class-string<BlockInterface> $blockClass */
    private function instantiateBlock(string $blockClass, BlockInstance $instance): BlockInterface
    {
        $reflection = new ReflectionClass($blockClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            /** @var BlockInterface $built */
            $built = $reflection->newInstance();

            return $built;
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $instance->props)) {
                $args[$name] = $this->coerce($param, $instance->props[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[$name] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $args[$name] = null;
            } else {
                throw new InvalidArgumentException("Missing required prop: {$name}");
            }
        }

        /** @var BlockInterface $block */
        $block = $reflection->newInstanceArgs($args);

        return $block;
    }

    private function coerce(ReflectionParameter $param, mixed $value): mixed
    {
        $type = $param->getType();
        if (! $type instanceof ReflectionNamedType) {
            return $value;
        }

        $name = $type->getName();

        if (enum_exists($name)) {
            $enumReflection = new ReflectionEnum($name);
            if ($enumReflection->isBacked() && (is_string($value) || is_int($value))) {
                /** @var class-string<BackedEnum> $name */
                return $name::from($value);
            }
            if (is_string($value)) {
                foreach ($enumReflection->getCases() as $case) {
                    if ($case->getName() === $value) {
                        return $case->getValue();
                    }
                }
            }
        }

        if ($name === 'array' && is_array($value)) {
            foreach ($param->getAttributes(ArrayOf::class) as $attr) {
                /** @var class-string $itemClass */
                $itemClass = $attr->newInstance()->itemClass;
                if (! class_exists($itemClass)) {
                    return $value;
                }

                return array_map(fn ($item) => $this->instantiateValueObject($itemClass, $item), $value);
            }
        }

        if (class_exists($name) && is_array($value)) {
            return $this->instantiateValueObject($name, $value);
        }

        return $value;
    }

    /**
     * @param  class-string  $class
     * @param  array<string, mixed>  $data
     */
    private function instantiateValueObject(string $class, array $data): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $data)) {
                $args[$name] = $this->coerce($param, $data[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[$name] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $args[$name] = null;
            }
        }

        return $reflection->newInstanceArgs($args);
    }
}
