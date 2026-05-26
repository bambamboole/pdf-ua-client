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
        ->assertScript('document.querySelector("iframe") === null')
        ->assertScript('document.querySelector(".template-builder") !== null');
});

it('opens block settings inline on the selected block', function (): void {
    $page = visit('/')
        ->click('Invoice')
        ->assertSee('PDF UA Kit GmbH')
        ->assertNoJavaScriptErrors();

    $page
        ->assertSee('Data')
        ->assertSee('Example')
        ->assertSee('Settings')
        ->assertSee('Config')
        ->assertSee('More')
        ->assertDontSee('Content')
        ->assertScript('document.querySelector("main [data-inline-block-editor] input[readonly]") === null')
        ->assertScript('document.querySelector("aside.border-l") === null')
        ->assertScript('[...document.querySelectorAll("[data-builder-tabs] button")].every((button) => button.textContent.trim() !== "Example Data")')
        ->assertScript('document.querySelector("main [data-inline-block-details][open] [data-inline-block-editor]") !== null');

    $page
        ->assertScript('document.querySelector("main [data-inline-block-details][open] [data-inline-editor-tab=\"data\"]") !== null')
        ->wait(0.5);

    $page
        ->assertSee('Lock')
        ->assertSee('Upload')
        ->assertScript('document.querySelector("main [data-inline-data-fields]") !== null')
        ->assertScript('document.querySelector("main [data-inline-data-fields] input[type=checkbox]") !== null')
        ->assertScript('(() => { const source = document.querySelector("[data-image-source-input]"); const editor = document.querySelector("[data-inline-block-editor]"); return source && editor ? source.getBoundingClientRect().right <= editor.getBoundingClientRect().right + 1 : false; })()');
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
        ->click('HTML')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->assertScript('document.querySelector("iframe")?.getAttribute("sandbox") === ""')
        ->withinFrame('iframe', fn ($frame) => $frame->assertSee('PDF UA Kit GmbH'))
        ->assertScreenshotMatches(fullPage: false, openDiff: false);
});
