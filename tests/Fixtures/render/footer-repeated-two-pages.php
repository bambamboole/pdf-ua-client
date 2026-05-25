<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

$lineItems = array_map(
    static fn (int $i): array => [
        'Service line '.$i,
        'Detailed project delivery item for page flow verification.',
        '1',
        number_format(125 + $i, 2, ',', '.').' EUR',
    ],
    range(1, 42),
);

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => [
            'page' => [
                'format' => 'A4',
                'locale' => 'de_DE',
                'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 25],
                'footer' => [
                    'repeat' => true,
                    'pageNumbers' => ['enabled' => true, 'position' => 'right'],
                    'rows' => [[
                        'blocks' => [
                            ['type' => 'text', 'id' => 'footer_note', 'config' => ['width' => '70%']],
                            ['type' => 'text', 'id' => 'footer_meta', 'config' => ['width' => '30%', 'typography' => ['align' => 'right']]],
                        ],
                    ]],
                ],
            ],
            'typography' => ['family' => 'Inter', 'size' => 9],
        ],
        'rows' => [
            ['blocks' => [[
                'type' => 'heading',
                'id' => 'title',
                'config' => ['level' => 1],
            ]]],
            ['blocks' => [[
                'type' => 'text',
                'id' => 'intro',
            ]]],
            ['blocks' => [[
                'type' => 'table',
                'id' => 'items',
                'config' => [
                    'style' => 'striped',
                    'columnAlignments' => ['left', 'left', 'right', 'right'],
                ],
            ]]],
        ],
    ],
    data: [
        'title' => ['text' => 'Repeated Footer Stress Invoice'],
        'intro' => ['text' => 'This fixture intentionally spans multiple pages so the footer repeat behavior is covered by visual PDF comparison.'],
        'items' => [
            'headers' => ['Item', 'Description', 'Qty', 'Total'],
            'rows' => $lineItems,
        ],
        'footer_note' => ['text' => 'PDF UA Kit GmbH · Musterstrasse 1 · 10115 Berlin'],
        'footer_meta' => ['text' => 'Page footer'],
    ],
    pdf: 'footer-repeated-two-pages.pdf',
    html: '<footer class="page-footer page-footer-repeated" role="contentinfo"><table class="row" role="presentation"><tr><td style="width: 70%;"><div class="block-4"><p>PDF UA Kit GmbH · Musterstrasse 1 · 10115 Berlin</p></div></td><td style="width: 30%;"><div class="block-5"><p>Page footer</p></div></td></tr></table></footer>
<table class="row" role="presentation"><tr><td><div class="block-1"><h1>Repeated Footer Stress Invoice</h1></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-2"><p>This fixture intentionally spans multiple pages so the footer repeat behavior is covered by visual PDF comparison.</p></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-3"><table class="data-table"><thead><tr><th style="text-align: left;">Item</th><th style="text-align: left;">Description</th><th style="text-align: right;">Qty</th><th style="text-align: right;">Total</th></tr></thead><tbody><tr><td style="text-align: left;">Service line 1</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">126,00 EUR</td></tr><tr><td style="text-align: left;">Service line 2</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">127,00 EUR</td></tr><tr><td style="text-align: left;">Service line 3</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">128,00 EUR</td></tr><tr><td style="text-align: left;">Service line 4</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">129,00 EUR</td></tr><tr><td style="text-align: left;">Service line 5</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">130,00 EUR</td></tr><tr><td style="text-align: left;">Service line 6</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">131,00 EUR</td></tr><tr><td style="text-align: left;">Service line 7</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">132,00 EUR</td></tr><tr><td style="text-align: left;">Service line 8</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">133,00 EUR</td></tr><tr><td style="text-align: left;">Service line 9</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">134,00 EUR</td></tr><tr><td style="text-align: left;">Service line 10</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">135,00 EUR</td></tr><tr><td style="text-align: left;">Service line 11</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">136,00 EUR</td></tr><tr><td style="text-align: left;">Service line 12</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">137,00 EUR</td></tr><tr><td style="text-align: left;">Service line 13</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">138,00 EUR</td></tr><tr><td style="text-align: left;">Service line 14</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">139,00 EUR</td></tr><tr><td style="text-align: left;">Service line 15</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">140,00 EUR</td></tr><tr><td style="text-align: left;">Service line 16</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">141,00 EUR</td></tr><tr><td style="text-align: left;">Service line 17</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">142,00 EUR</td></tr><tr><td style="text-align: left;">Service line 18</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">143,00 EUR</td></tr><tr><td style="text-align: left;">Service line 19</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">144,00 EUR</td></tr><tr><td style="text-align: left;">Service line 20</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">145,00 EUR</td></tr><tr><td style="text-align: left;">Service line 21</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">146,00 EUR</td></tr><tr><td style="text-align: left;">Service line 22</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">147,00 EUR</td></tr><tr><td style="text-align: left;">Service line 23</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">148,00 EUR</td></tr><tr><td style="text-align: left;">Service line 24</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">149,00 EUR</td></tr><tr><td style="text-align: left;">Service line 25</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">150,00 EUR</td></tr><tr><td style="text-align: left;">Service line 26</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">151,00 EUR</td></tr><tr><td style="text-align: left;">Service line 27</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">152,00 EUR</td></tr><tr><td style="text-align: left;">Service line 28</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">153,00 EUR</td></tr><tr><td style="text-align: left;">Service line 29</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">154,00 EUR</td></tr><tr><td style="text-align: left;">Service line 30</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">155,00 EUR</td></tr><tr><td style="text-align: left;">Service line 31</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">156,00 EUR</td></tr><tr><td style="text-align: left;">Service line 32</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">157,00 EUR</td></tr><tr><td style="text-align: left;">Service line 33</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">158,00 EUR</td></tr><tr><td style="text-align: left;">Service line 34</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">159,00 EUR</td></tr><tr><td style="text-align: left;">Service line 35</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">160,00 EUR</td></tr><tr><td style="text-align: left;">Service line 36</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">161,00 EUR</td></tr><tr><td style="text-align: left;">Service line 37</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">162,00 EUR</td></tr><tr><td style="text-align: left;">Service line 38</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">163,00 EUR</td></tr><tr><td style="text-align: left;">Service line 39</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">164,00 EUR</td></tr><tr><td style="text-align: left;">Service line 40</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">165,00 EUR</td></tr><tr><td style="text-align: left;">Service line 41</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">166,00 EUR</td></tr><tr><td style="text-align: left;">Service line 42</td><td style="text-align: left;">Detailed project delivery item for page flow verification.</td><td style="text-align: right;">1</td><td style="text-align: right;">167,00 EUR</td></tr></tbody></table></div></td></tr></table>',
);
