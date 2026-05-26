<?php

declare(strict_types=1);
namespace Workbench\App\Support;

use RuntimeException;

final readonly class TemplateFixtureRepository
{
    public function __construct(
        private string $root,
    ) {}

    /** @return list<TemplateFixture> */
    public function examples(): array
    {
        return $this->loadGroup('examples');
    }

    /** @return list<TemplateFixture> */
    public function regressions(): array
    {
        return $this->loadGroup('regressions');
    }

    /** @return list<TemplateFixture> */
    public function all(): array
    {
        return [...$this->examples(), ...$this->regressions()];
    }

    /** @return list<TemplateFixture> */
    private function loadGroup(string $group): array
    {
        $directory = "{$this->root}/{$group}";

        if (! is_dir($directory)) {
            return [];
        }

        $paths = glob($directory.'/*', GLOB_ONLYDIR) ?: [];
        sort($paths);

        return array_map(
            fn (string $path): TemplateFixture => $this->loadFixture($group, $path),
            $paths,
        );
    }

    private function loadFixture(string $group, string $path): TemplateFixture
    {
        $slug = basename($path);
        $htmlPath = $path.'/expected.html';
        $pdfPath = $path.'/expected.pdf';
        $contractPath = $path.'/contract.json';

        return new TemplateFixture(
            group: $group,
            slug: $slug,
            title: $this->titleFromSlug($slug),
            path: $path,
            template: $this->readJsonObject($path.'/template.json'),
            data: $this->dataMap($this->readJsonObject($path.'/data.json')),
            contract: is_file($contractPath) ? $this->readJsonObject($contractPath) : null,
            html: is_file($htmlPath) ? (string) file_get_contents($htmlPath) : null,
            htmlPath: is_file($htmlPath) ? $htmlPath : null,
            pdfPath: is_file($pdfPath) ? $pdfPath : null,
        );
    }

    /** @return array<string, mixed> */
    private function readJsonObject(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Missing fixture file: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException("Fixture file must contain a JSON object: {$path}");
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function dataMap(array $data): array
    {
        $map = [];

        foreach ($data as $id => $values) {
            if (is_array($values)) {
                $map[(string) $id] = $values;
            }
        }

        return $map;
    }

    private function titleFromSlug(string $slug): string
    {
        return str((string) preg_replace('/[-_]+/', ' ', $slug))->title()->toString();
    }
}
