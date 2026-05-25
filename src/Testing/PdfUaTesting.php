<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Testing;

use Pest\Expectation;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

final class PdfUaTesting
{
    public static function register(): void
    {
        expect()->extend('toContainOnlySafeCss', function (): Expectation {
            /** @var Expectation $this */
            $html = (string) $this->value;

            $unsafePatterns = [
                'display:\s*flex',
                'display:\s*grid',
                'transform:',
                'linear-gradient',
                'radial-gradient',
                'var\(--',
                'calc\(.*[+\-*\/].*\)\s*\)',
            ];

            foreach ($unsafePatterns as $pattern) {
                if (preg_match("/{$pattern}/i", $html) === 1) {
                    throw new AssertionFailedError("HTML contains unsafe CSS pattern: {$pattern}");
                }
            }

            Assert::assertTrue(true);

            return $this;
        });
    }
}
