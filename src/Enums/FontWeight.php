<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Enums;

enum FontWeight: int
{
    case Light = 300;
    case Regular = 400;
    case Medium = 500;
    case SemiBold = 600;
    case Bold = 700;
}
