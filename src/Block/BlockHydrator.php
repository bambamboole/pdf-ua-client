<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Block;

use BackedEnum;
use Bambamboole\PdfUaClient\Attributes\ArrayOf;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;
use Bambamboole\PdfUaClient\Exceptions\BlockDataValidationException;
use Bambamboole\PdfUaClient\Exceptions\BlockHydrationException;
use Bambamboole\PdfUaClient\Support\SchemaAwareNormalizer;
use Bambamboole\PdfUaClient\Template\BlockInstance;
use InvalidArgumentException;
use Opis\JsonSchema\Validator;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

final class BlockHydrator
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly PropsReflector $reflector,
    ) {}

    public function hydrate(BlockInstance $instance): HydratedBlock
    {
        $blockClass = $this->registry->resolve($instance->type);
        $configClass = $this->registry->configClass($instance->type);

        $this->validate($blockClass, $instance);

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
    private function validate(string $blockClass, BlockInstance $instance): void
    {
        $schemas = $this->reflector->reflectBlock($blockClass);
        $validator = new Validator;

        $type = $instance->type;

        $dataSchema = $schemas['data'];
        $dataNormalized = SchemaAwareNormalizer::normalize($instance->props, $dataSchema);
        $dataResult = $validator->validate($dataNormalized, json_decode((string) json_encode($dataSchema)));
        if (! $dataResult->isValid()) {
            throw new BlockDataValidationException(
                "Block '{$type}' failed prop validation",
                $type,
                $dataResult->error(),
            );
        }

        $configSchema = $schemas['config'];
        $configNormalized = SchemaAwareNormalizer::normalize($instance->config, $configSchema);
        $configResult = $validator->validate($configNormalized, json_decode((string) json_encode($configSchema)));
        if (! $configResult->isValid()) {
            throw new BlockDataValidationException(
                "Block '{$type}' failed config validation",
                $type,
                $configResult->error(),
            );
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
