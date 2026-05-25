<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

final class ExampleRegistry
{
    /** @var list<array<string, mixed>> */
    private array $examples = [];

    /** @param array<string, mixed> $document */
    public function register(array $document): self
    {
        $this->examples[] = $document;

        return $this;
    }

    public function flush(): self
    {
        $this->examples = [];

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->examples;
    }
}
