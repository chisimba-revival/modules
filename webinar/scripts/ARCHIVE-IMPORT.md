# Learn-the-Birds historical archive adapter

These CLI adapters target the protected migration site only. They are not general-purpose import endpoints.

1. Run `export-wordpress-archive.php` in the original WordPress installation. It uses SHORTINIT and reads public/expired historical event listings, linked public organisers, attachment metadata and local inline images. It excludes drafts, cancelled and future events, audience records and operational secrets. Review `issues` and the selected dates before proceeding.
2. Package the referenced uploads with their relative paths and verify every SHA-256. Keep the original manifest immutable. Any unsupported image conversion must retain the original path/hash and document the derivative; do not broaden the image allowlist.
3. Back up the staging database and module source. Place reviewed manifest at `/tmp/webinar-full/trial-export.json` and files under `/tmp/webinar-full/media/` in its web container. The filename is inherited from the trial adapter.
4. Run `import-full-archive.php` without arguments for a media dry run. Then `--apply` indexes public media and preflights record identities/hashes before a database transaction publishes records. Only an exact original-source baseline may be upgraded to managed inline-media URLs. Other edited records require review. Media copies/index entries precede the record transaction and are intentionally reusable after an interrupted run.
5. Repeat `--apply`: every record should be unchanged. Verify every title, date, timezone, biography, description, recording link, speaker relationship and image hash. Compare upcoming record hashes with the backup. Check public archive pages, details, speaker popups and image delivery.

15 September 2026 batch: 107 historical webinars, 67 linked speakers, 322 distinct images; 69 recording links and 38 source entries without recordings. Dates span 4 February 2021 to 20 August 2026. Both source `expired` entries are included as permanent archive content. One SVG logo was rendered to a transparent PNG derivative. Four upcoming webinars retained their original hashes. The three trial webinars were reconciled without duplicates.
