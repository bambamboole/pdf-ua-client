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
        ->assertNoJavaScriptErrors()
        ->assertScript('document.querySelectorAll("main [data-inline-block-details][open]").length === 0');

    $page->click('More');

    $page
        ->assertSee('Data')
        ->assertSee('Example')
        ->assertSee('Settings')
        ->assertSee('Config')
        ->assertSee('More')
        ->assertScript('document.querySelector("main [data-inline-block-editor] input[readonly]") === null')
        ->assertScript('document.querySelector("aside.border-l") === null')
        ->assertScript('[...document.querySelectorAll("[data-inline-editor-tab]")].every((button) => button.textContent.trim() !== "Content")')
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
        ->assertSee('Footer')
        ->assertSee('Page numbers')
        ->assertSee('Repeat')
        ->assertScript('document.querySelector("[data-edit-canvas]")?.style.maxWidth.endsWith("px")')
        ->assertScript('document.querySelector("[data-footer-canvas]") !== null')
        ->assertScript('document.querySelectorAll("[data-footer-canvas] select").length === 0')
        ->assertScript('document.querySelectorAll("[data-footer-canvas] [data-new-row-zone=\"footer\"]").length === 1')
        ->assertScript('(() => { const body = document.querySelector("[data-body-canvas]"); const footer = document.querySelector("[data-footer-canvas]"); return body && footer ? Math.abs(body.getBoundingClientRect().width - footer.getBoundingClientRect().width) <= 1 : false; })()')
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
})->skip(fn (): bool => filter_var(getenv('CI'), FILTER_VALIDATE_BOOLEAN), 'Visual regression baseline is environment-specific; run locally via composer test:browser.');

it('renders the shipping label example preview without browser errors', function (): void {
    visit('/')
        ->click('Shipping Label')
        ->click('HTML')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->withinFrame('iframe', fn ($frame) => $frame->assertSee('1Z999AA10123456784'));
});

it('pins the preview footer to the page bottom and shows the page number', function (): void {
    $page = visit('/')
        ->click('Invoice')
        ->click('HTML')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    $page->withinFrame('iframe', function ($frame): void {
        $frame->assertSee('1 / 1');

        $frame->assertScript(
            '(() => { const b = document.body.getBoundingClientRect(); return Math.round(b.height) >= 1000; })()',
        );

        $frame->assertScript(
            '(() => { const f = document.querySelector("footer.page-footer-preview"); const bh = document.body.getBoundingClientRect().height; if (!f) { return false; } const fb = f.getBoundingClientRect().bottom; return fb > bh - 130 && fb <= bh + 1; })()',
        );
    });
});
