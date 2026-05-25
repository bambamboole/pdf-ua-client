<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => [
            'page' => [
                'format' => 'A4',
                'foldMarks' => true,
                'punchMarks' => true,
            ],
        ],
        'rows' => [
            ['blocks' => [
                ['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]],
            ]],
            ['blocks' => [
                ['type' => 'text', 'id' => 'intro'],
            ]],
        ],
    ],
    data: [
        'title' => ['text' => 'Page Marks'],
        'intro' => ['text' => 'This fixture verifies fold and punch marks in the printed PDF output.'],
    ],
    pdf: 'page-marks.pdf',
    html: '<div class="page-marks" aria-hidden="true"><span class="page-mark page-mark-fold-top"></span><span class="page-mark page-mark-fold-bottom"></span><span class="page-mark page-mark-punch"></span></div>
<table class="row" role="presentation"><tr><td><div class="block-1"><h1>Page Marks</h1></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-2"><p>This fixture verifies fold and punch marks in the printed PDF output.</p></div></td></tr></table>',
);
