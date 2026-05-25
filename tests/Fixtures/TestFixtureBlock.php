<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Tests\Fixtures;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('test-fixture', config: TestFixtureBlockConfig::class)]
final readonly class TestFixtureBlock implements BlockInterface
{
    public function __construct(public string $text = '') {}

    public function render(TestFixtureBlockConfig $config): string
    {
        return "<p>{$this->text}</p>";
    }
}
