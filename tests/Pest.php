<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Testing\PdfUaTesting;
use Bambamboole\PdfUaClient\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

PdfUaTesting::register();
