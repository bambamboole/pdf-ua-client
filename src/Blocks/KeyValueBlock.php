<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\ArrayOf;
use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Example;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Config\KeyValueConfig;
use Bambamboole\PdfUaClient\Config\KeyValueField;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('key-value', config: KeyValueConfig::class)]
#[Title('Key / Value')]
final readonly class KeyValueBlock implements BlockInterface
{
    /**
     * @param  list<KeyValuePair>  $entries
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        #[ArrayOf(KeyValuePair::class)]
        #[Example([['label' => 'Label', 'value' => 'Value']])]
        public array $entries = [],
        public array $values = [],
    ) {}

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
        if ($config->fields !== []) {
            return array_map(
                fn (KeyValueField|array $field): array => [
                    'label' => $field instanceof KeyValueField ? $field->label : (string) ($field['label'] ?? ''),
                    'value' => $this->stringValue($this->values[$field instanceof KeyValueField ? $field->key : (string) ($field['key'] ?? '')] ?? ''),
                ],
                $config->fields,
            );
        }

        return array_map(
            fn (KeyValuePair $pair): array => ['label' => $pair->label, 'value' => $pair->value],
            $this->entries,
        );
    }

    private function stringValue(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }
}
