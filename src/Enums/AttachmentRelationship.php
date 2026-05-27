<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Enums;

enum AttachmentRelationship: string
{
    case Source = 'Source';
    case Data = 'Data';
    case Alternative = 'Alternative';
    case Supplement = 'Supplement';
    case Unspecified = 'Unspecified';
}
