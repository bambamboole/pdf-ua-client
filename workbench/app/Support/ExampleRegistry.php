<?php

declare(strict_types=1);
namespace Workbench\App\Support;

final class ExampleRegistry
{
    /** @var list<array{title: string, template: array<string, mixed>, data: array<string, mixed>}> */
    private array $examples = [];

    /**
     * @param  array<string, mixed>  $template
     * @param  array<string, mixed>  $data
     */
    public function register(string $title, array $template, array $data = []): self
    {
        if ($data !== [] && ! isset($template['data']['example'])) {
            $template['data']['example'] = $data;
        }

        $this->examples[] = ['title' => $title, 'template' => $template, 'data' => $data];

        return $this;
    }

    public function flush(): self
    {
        $this->examples = [];

        return $this;
    }

    /** @return list<array{title: string, template: array<string, mixed>, data: array<string, mixed>}> */
    public function all(): array
    {
        return $this->examples;
    }
}
