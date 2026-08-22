# Content Ingest Service

`ingestservice` is a reusable boundary between source formats and Chisimba modules.
It parses a source into the consumer-neutral `chisimba.ingest-document/v1`, validates that canonical model,
produces byte-free dry-run previews, and delivers valid content through a named
consumer adapter. Source adapters never call consumer persistence APIs.

## Canonical model

The document contains source metadata (including a SHA-256 fingerprint), ordered
blocks, binary assets, and structured issues. Blocks represent headings, paragraphs,
images, lists, or tables without assigning consumer-specific meaning to them. Every issue has `severity`, `code`,
`message`, and `path`. Asset references use `ingest-asset://<id>` until a consumer
materialises them.

The DOCX and ODT adapters record heading levels and named paragraph styles without mapping
them to chapters, pages, essays, or any other domain object. `styleMap` can override
source-style interpretation.

Package guards limit source size, archive entries, expanded size, compression ratio,
and individual image size. The adapters preserve safe hyperlinks, lists, basic tables,
line breaks, image descriptions and captions; the DOCX adapter also retains common
inline emphasis. Content that cannot yet be retained is reported as a structured issue.

Optional capabilities transform the neutral document for a particular use case.
`contextcontentingestprofile` is the reference capability: Heading 1 creates a chapter,
Chapter Overview supplies its overview, Heading 2 creates a page, and lower headings
remain inside pages. An essay consumer can instead retain the ordered neutral blocks.

## Public service API

```php
$service = $this->getObject('ingestservice', 'ingestservice');
$preview = $service->preview($path, $options);
$document = $service->parse($path, $options);
$projection = $service->applyCapability(
    $document,
    'contextcontent',
    'contextcontentingestprofile'
);
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
consumer detail without changing the parser or neutral canonical contract.
