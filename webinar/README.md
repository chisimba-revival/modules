# Webinar archive — first migration slice

Public read-only catalogue, permanent ID-based detail pages and shared speaker pages. Uses skin publication cards, UI icons, language strings, rich-text sanitisation and the contentblocks composition renderer. No accounts are required for public archive viewing. Unpublished records are excluded from both lists and detail routes.

The single main webinar image is used intact on catalogue and detail pages. A WordPress banner is preferred; the featured image is a fallback and its original file ID remains in provenance. Imported files use File Manager records and its ID-based delivery route.

## Trial adapter

`scripts/import-trial.php` is a CLI-only, migration-host-restricted adapter for the reviewed `trial-export.json` format. Defaults to no-write media validation; `--apply` registers media and imports records. Stable source keys prevent duplicate records; changed content is rejected pending explicit review rather than overwriting edits. Reruns resume partially completed imports. Back up the destination before broader batches. The module does not copy or schedule audience or email jobs.

Payloads are version-one archive fields: normalised description, managed image URL and file ID, original source slug, and for webinars recording URL, time zone, ordered speaker IDs and legacy thumbnail file ID. Rows store kind, publication status, source identity/hash, title and local historical datetime. This first slice only imports completed published records; scheduling/editor/registration state is not implemented.

## Remaining work

Authorised speaker and webinar editing, search/pagination, broader historical data reconciliation, inline media rewriting, redirects, transactional batch reporting and general-purpose import tooling. The adapter does not fetch arbitrary external images. Speaker photos can currently also be logos from WordPress; review the imported content before wider release. Date display currently uses English month names; integrate the configured date-format service before general multilingual release.

Audience subscriptions, optional accounts, supporter tiers and operational reminders remain separate future slices. Do not enable email delivery or cut over the live domain as part of this module trial.

## Validation

`php tests/archive_contract_test.php` covers repeat import, source conflicts, draft exclusion, invalid IDs, output escaping and uncropped image rendering. Staging verification additionally checks actual media delivery, all three webinar and seven speaker pages, and desktop/mobile presentation. No tests create WordPress records or send email.
