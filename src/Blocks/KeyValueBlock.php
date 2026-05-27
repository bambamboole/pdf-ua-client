<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Config\KeyValueConfig;
use Bambamboole\PdfUaClient\Config\KeyValueField;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;
use Bambamboole\PdfUaClient\Contracts\HasDynamicData;
use stdClass;

#[Block('key-value', config: KeyValueConfig::class)]
#[Title('Key / Value')]
final readonly class KeyValueBlock implements BlockInterface, HasDynamicData
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public array $values = [],
    ) {}

    public static function dataSchema(array $config): array
    {
        $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];

        $properties = [];
        foreach ($fields as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key !== '') {
                $properties[$key] = ['type' => 'string'];
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties === [] ? new stdClass : $properties,
            'additionalProperties' => false,
        ];

        if ($properties !== []) {
            $schema['required'] = array_keys($properties);
        }

        return $schema;
    }

    public static function mapRuntimeData(array $config, array $data): array
    {
        return ['values' => $data];
    }

    public function render(KeyValueConfig $config): string
    {
        $rows = '';
        foreach ($this->renderedPairs($config) as $pair) {
            $rows .= '<tr><td>'.e($pair['label']).'</td><td>'.e($pair['value']).'</td></tr>';
        }

        return "<table class=\"key-value\"><tbody>{$rows}</tbody></table>";
    }

    /** @return list<array{label: string, value: string}> */
    private function renderedPairs(KeyValueConfig $config): array
    {
        return array_map(
            fn (KeyValueField $field): array => [
                'label' => $field->label,
                'value' => $this->stringValue($this->values[$field->key] ?? ''),
            ],
            $config->fields,
        );
    }

    private function stringValue(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }
}
