<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\ArrayOf;
use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Config\KeyValueConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('key-value', config: KeyValueConfig::class)]
final class KeyValueBlock implements BlockInterface
{
    /** @param  list<KeyValuePair>  $entries */
    public function __construct(
        #[ArrayOf(KeyValuePair::class)]
        public readonly array $entries = [],
    ) {}

    public function render(KeyValueConfig $config): string
    {
        $rows = '';
        foreach ($this->entries as $pair) {
            $rows .= '<tr><td>'.e($pair->label).'</td><td>'.e($pair->value).'</td></tr>';
        }

        return "<table><tbody>{$rows}</tbody></table>";
    }
}
