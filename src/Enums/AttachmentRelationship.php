<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient\Enums;

enum AttachmentRelationship: string
{
    case Alternative = 'Alternative';
    case Data = 'Data';
    case Source = 'Source';
    case Supplement = 'Supplement';
    case Unspecified = 'Unspecified';
}
