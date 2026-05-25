<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Enums;

enum PageFormat: string
{
    case A4 = 'A4';
    case A5 = 'A5';
    case Letter = 'Letter';
    case Legal = 'Legal';
}
