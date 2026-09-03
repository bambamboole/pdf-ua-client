# pdf-ua-client

Laravel client for rendering accessible PDF/UA documents with
[`bambamboole/pdf-ua-api`](https://github.com/bambamboole/pdf-ua-api).

This package targets the template v2 API. Template authoring belongs to
[`@bambamboole/pdf-ua-template-builder`](https://www.npmjs.com/package/@bambamboole/pdf-ua-template-builder).

## Requirements

- PHP `^8.4`
- Laravel `^13.0`

## Installation

```bash
composer require bambamboole/pdf-ua-client
```

Configure the API through environment variables:

```dotenv
PDF_UA_API_URL=https://pdf-ua-api.example.com
PDF_UA_API_TOKEN=
PDF_UA_API_CONNECT_TIMEOUT=5
PDF_UA_API_TIMEOUT=120
```

## Render a v2 template

The package registers `PdfClient` as a Laravel container binding.

```php
use Bambamboole\PdfUaClient\Contracts\PdfClient;
use Bambamboole\PdfUaClient\PdfTemplate;

$document = app(PdfClient::class)->renderTemplate(new PdfTemplate(
    template: $template,
    data: $data,
));

Storage::put('quotes/Q-1000.pdf', $document->contents);
```

`RenderedPdf` also exposes the optional `documentUuid` returned by the API.

## Attach files and XMP metadata

Attachments and custom XMP schemas are added to the v2 template before it is sent to the API.

```php
use Bambamboole\PdfUaClient\Attachment;
use Bambamboole\PdfUaClient\Contracts\PdfClient;
use Bambamboole\PdfUaClient\PdfTemplate;
use Bambamboole\PdfUaClient\XmpProperty;
use Bambamboole\PdfUaClient\XmpSchema;

$document = app(PdfClient::class)->renderTemplate(new PdfTemplate(
    template: $template,
    data: $data,
    attachments: [Attachment::facturX($invoiceXml)],
    xmpSchemas: [new XmpSchema(
        namespace: 'urn:example:invoice:pdfa:CrossIndustryDocument:invoice:1p0#',
        prefix: 'fx',
        name: 'Invoice metadata',
        properties: [new XmpProperty('DocumentType', 'INVOICE')],
    )],
));
```

## Render HTML

```php
use Bambamboole\PdfUaClient\Contracts\PdfClient;
use Bambamboole\PdfUaClient\HtmlDocument;

$document = app(PdfClient::class)->renderHtml(new HtmlDocument(
    html: $html,
    baseUrl: 'https://assets.example.com/',
));
```

## Direct upload

Use `renderTemplateTo()` or `renderHtmlTo()` with a presigned upload URL. The API uploads the PDF and the client returns an `UploadedPdf` result instead of transferring the PDF bytes back through Laravel.

```php
$result = app(PdfClient::class)->renderTemplateTo(
    new PdfTemplate(template: $template, data: $data),
    $presignedUploadUrl,
);
```

## Template data helpers

`TemplateData` contains the reusable v2 data-shaping operations used by consumers:

- interpolate `{{ dot.notation }}` placeholders throughout a template;
- enumerate body and footer blocks;
- build safe text, HTML, key-value, and table overrides;
- normalize authored multiline text.

## Development

```bash
composer install
composer check
```

The package skeleton is maintained by `bambamboole/extended-testbench`. Run `composer fix` before committing.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
