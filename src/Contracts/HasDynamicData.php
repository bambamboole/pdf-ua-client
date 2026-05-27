<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Contracts;

/**
 * A block whose runtime-data shape is derived from its own config (such as a
 * table from its columns) rather than from its constructor signature.
 */
interface HasDynamicData
{
    /**
     * Build the runtime-data JSON Schema for this block from its stored config.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function dataSchema(array $config): array;

    /**
     * Map flat runtime data for this block into constructor props.
     *
     * @param  array<string, mixed>  $config
     * @param  array<array-key, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mapRuntimeData(array $config, array $data): array;
}
