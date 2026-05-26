<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

final readonly class TemplateDataMerger
{
    /**
     * @param  array<string, mixed>  $runtimeData
     * @return array<string, mixed>
     */
    public function runtimeData(Template $template, array $runtimeData): array
    {
        return $this->mergeMaps($template->data->defaults, $runtimeData, $template->data->constants);
    }

    /**
     * @param  array<string, mixed>  $maps
     * @return array<string, mixed>
     */
    private function mergeMaps(array ...$maps): array
    {
        $merged = [];

        foreach ($maps as $map) {
            foreach ($map as $blockId => $data) {
                $merged[$blockId] = $this->mergeValue($merged[$blockId] ?? [], $data);
            }
        }

        return $merged;
    }

    private function mergeValue(mixed $base, mixed $override): mixed
    {
        if (! is_array($base) || ! is_array($override) || array_is_list($base) || array_is_list($override)) {
            return $override;
        }

        foreach ($override as $key => $value) {
            $base[$key] = $this->mergeValue($base[$key] ?? null, $value);
        }

        return $base;
    }
}
