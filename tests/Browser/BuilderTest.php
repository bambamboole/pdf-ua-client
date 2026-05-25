<?php

declare(strict_types=1);

it('loads the builder without browser smoke failures', function (): void {
    visit('/')
        ->assertSee('Build')
        ->assertSee('Heading')
        ->assertNoSmoke()
        ->assertScript('document.querySelector("iframe") === null');
});

it('renders the invoice example preview and matches the browser screenshot', function (): void {
    visit('/')
        ->click('Invoice')
        ->click('Render')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->assertScript('document.querySelector("iframe")?.getAttribute("sandbox") === ""')
        ->withinFrame('iframe', fn ($frame) => $frame->assertSee('ACME GmbH'))
        ->assertScreenshotMatches(fullPage: false, openDiff: false);
});
