<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Template\Row;

final readonly class PageFooterConfig
{
    /**
     * @param  list<Row>  $rows
     */
    public function __construct(
        #[Title('Repeat')]
        #[Description('Repeat the footer on every rendered page.')]
        public bool $repeat = true,
        #[Title('Rows')]
        #[Description('Footer rows rendered after the page body.')]
        public array $rows = [],
        #[Title('Pagination')]
        #[Description('Page number display settings for this footer.')]
        public PageNumbersConfig $pageNumbers = new PageNumbersConfig,
    ) {}
}
