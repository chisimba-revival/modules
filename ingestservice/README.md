# Content Ingest Service

`ingestservice` is a reusable boundary between source formats and Chisimba modules.
It parses a source into `chisimba.content-ingest/v1`, validates that canonical model,
produces byte-free dry-run previews, and delivers valid content through a named
consumer adapter. Source adapters never call consumer persistence APIs.

## Canonical model

The document contains source metadata (including a SHA-256 fingerprint), chapters,
pages, binary assets, and structured issues. Every issue has `severity`, `code`,
`message`, and `path`. Asset references use `ingest-asset://<id>` until a consumer
materialises them.

The DOCX adapter maps Heading 1 to chapters, Chapter Overview to the current chapter
overview, Heading 2 to pages, and Heading 3 through Heading 6 to headings inside the
current page. `styleMap` can override or extend these roles.

## Public service API

```php
$service = $this->getObject('ingestservice', 'ingestservice');
$preview = $service->preview($path, $options);
$document = $service->parse($path, $options);
$result = $service->deliver(
    $document,
    'contextcontent',
    'contextcontentingestconsumer',
    array('target' => $contextCode, 'contextcode' => $contextCode, 'language' => 'en')
);
```

Delivery is idempotent for the tuple `(source fingerprint, consumer module, target)`.
Passing `force => true` starts another delivery. The first consumer stores DOCX images
as data URIs in rich-text content; a later managed-file asset sink can replace that
consumer detail without changing the parser or canonical contract.
