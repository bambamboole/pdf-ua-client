<?php

declare(strict_types=1);

it('loads the builder without browser smoke failures', function (): void {
    visit('/')
        ->assertSee('Build')
        ->assertSee('Heading')
        ->assertSee('Page settings')
        ->assertSee('Format')
        ->assertNoSmoke()
        ->assertScript('document.querySelector("aside.border-l") === null')
        ->assertScript('document.querySelector("iframe") === null');
});

it('opens block settings inline on the selected block', function (): void {
    $page = visit('/')
        ->click('Invoice')
        ->assertSee('PDF UA Kit GmbH')
        ->assertNoJavaScriptErrors();

    $page
        ->assertSee('Content')
        ->assertSee('Config')
        ->assertScript('document.querySelector("aside.border-l") === null')
        ->assertScript('document.querySelector("main [data-inline-block-details][open] [data-inline-block-editor]") !== null');
});

it('sizes the build canvas to the selected page format', function (): void {
    visit('/')
        ->click('Invoice')
        ->assertScript('document.querySelector("[data-edit-canvas]")?.getBoundingClientRect().width <= 810')
        ->assertScript('document.querySelector("[data-edit-canvas]")?.style.maxWidth === "210mm"')
        ->assertNoJavaScriptErrors();
});

it('renders the invoice example preview and matches the browser screenshot', function (): void {
    visit('/')
        ->click('Invoice')
        ->click('Render')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->assertScript('document.querySelector("iframe")?.getAttribute("sandbox") === ""')
        ->withinFrame('iframe', fn ($frame) => $frame->assertSee('PDF UA Kit GmbH'))
        ->assertScreenshotMatches(fullPage: false, openDiff: false);
});
