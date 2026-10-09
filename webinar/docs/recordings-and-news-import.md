# Learn-the-Birds: Recordings and WordPress articles

Implemented on protected `migrate.learnthebirds.com`, 15 September 2026. Live WordPress, DNS, mail, subscribers and registrations are unchanged.

## Result

Navigation is **Webinars | Archive | News and blog | Recordings**.

- Recordings contains 120 distinct full-length videos: 112 from the channel Videos tab and eight from Live. The 20 IDs found in Shorts are excluded. This is based on YouTube's tab classification, not an unreliable duration cutoff. Videos keep their source-tab order, followed by Live recordings; they are not globally merged by publication date.
- Six cards load initially. Thumbnails are lazy, space is reserved, and no YouTube player loads before activation. A shared native dialog opens the player; Escape/Close removes it. Load more fetches six cards without navigation, guards repeated clicks, preserves scroll position and supports retry. Normal links provide the non-JavaScript fallback. Subscribe opens the existing YouTube channel in a new tab.
- SimpleBlog has 27 published articles, dated 18 July 2020–8 April 2023, and the existing empty “Demo post” draft. Public credits are Derek Keats (22), Lynette Rudman (4), and Faraaz Abdool (1). The draft is credited to Derek. Editorial ownership belongs to the existing Derek account; no WordPress accounts or login rights were imported.
- Preserved dates, titles, featured images, image captions/alt text, categories/tags, source URLs and original WordPress HTML. The 61 taxonomy terms include category ancestors. 673 distinct local image files have verified SHA-256 matches and are registered through managed public files. Existing external Flickr images remain external.
- Elementor content becomes 442 native blocks: 258 text, 168 hero/image, 13 video/audio embeds and three sliders. WordPress post-comments widgets and plugin execution are omitted. Xeno-canto bird-sound embeds use the shared media allowlist. The raw original is retained for audit, never directly executed as a template.

## Architecture and boundaries

`contentblocks` owns reusable video-card rendering, on-demand playback and media validation. Webinar owns its channel catalogue, public recordings route and six-item pagination. SimpleBlog owns posts, normal permissions and structured compositions. Classification owns terms and links. No new frontend vendor package is used.

Imported author credit is independent of the editor account. A guest author's post does not accidentally acquire the editor's biography. Import fingerprints include content, publication state, dates, attribution and composition. Re-import aborts on source/editorial changes or classification conflicts instead of overwriting them. A repeat of this batch returned 28 unchanged posts.

This is a reviewed snapshot. **Automatic YouTube refresh is not configured.** The exporter uses public channel pages; those formats can change. Investigate exporter failures rather than replacing a working catalogue with partial data. There is no copied API credential. The importer is intentionally restricted to migration staging.

## Repeating or refreshing

1. Back up the destination database and matching source. The pre-batch backup is `/srv/learnthebirds-migrate/backups/content-20260915/` (`database.sql`, `source.tar`). Retain any later editorial changes before considering a restore.
2. Deploy matching framework/module sources. Register/update contentblocks, SimpleBlog and Webinar. The legacy registration path does not itself run every SQL upgrade and its index builder does not reliably enforce uniqueness. Run `contentblocks/scripts/configure-publication-imports.php` in the staging web container: it adds only missing nullable import fields and explicitly verifies unique article/source/video identities. Do not treat an installer success message as proof of the schema.
3. WordPress export: execute `simpleblog/scripts/export-wordpress-posts.php` with the existing WordPress PHP runtime and its site files. It is read-only. Review the JSON, including issues/private content, widget types, attachments and term ancestry. Package only the listed media, keeping relative paths and verifying hashes. Staging inputs are `/tmp/ltb-posts/source.json` and `/tmp/ltb-posts/media/`. Run `simpleblog/scripts/import-wordpress-posts.php` first without arguments, then with `--apply` for an authorised reviewed batch.
4. The archived source batch is `/srv/learnthebirds-migrate/imports/posts-20260915/` (`source.json`, `media.tar`, `youtube.json`). Media indexing precedes the article transaction. An interrupted import may therefore leave reusable managed files, but must not leave partially committed article/term records. A changed existing file causes an abort, not an overwrite.
5. YouTube export: run `webinar/scripts/export-youtube-channel.py UC0oA8XzmNqvA_g0EZB4K6bQ <reviewed-json>`. Review tab counts, IDs and Shorts exclusions. Place the reviewed file at `/tmp/ltb-youtube-videos.json` in the staging web container. Run `webinar/scripts/import-youtube-channel.php` without arguments, then `--apply`. Replacement is transactional and limited to that channel. Do not schedule an unreviewed exporter or reuse these staging-only import scripts on production.
6. Validate stored records/counts, repeat-import behaviour, public HTTP responses and browser interactions. Module versions for this batch: contentblocks 1.022, SimpleBlog 0.070, Webinar 0.008.

Known installer failure encountered and corrected: the legacy insert wrapper can return a truthy ID after a database error. Importers now check direct database errors and read back the stored rows; the first failed attempt created no article rows. The final stored count is 28, independently checked. The new video table also requires the supported `charset` schema key.

## Validation

- All 673 local image hashes and file records verified; all 28 imported content fingerprints, author credits and original bodies verified.
- All 28 compositions render identically after passing through the existing editor's composition loader.
- All 27 public article detail pages respond successfully; the draft is denied anonymously. Three listing pages contain exactly those 27 distinct posts. Search, category and tag filters, and RSS pass.
- All 20 recordings batches contain six entries, with exactly the reviewed 120 IDs, no duplicates and no Shorts; malformed page values are rejected. Normal non-Ajax page 2 works.
- Local isolated Chrome tests use captured public staging HTML/JSON/images and the matching application assets. They pass click-to-play, player removal on Escape, contextual Help/Escape, Load more/scroll preservation, failed-request retry, all 120 unique cards, final button hiding, UTF-8 titles and mobile-width checks. The YouTube iframe is stubbed in these interaction tests; actual video playback/YouTube availability is not asserted.
- PHP lint and behavioural/contract tests pass for shared composition, media/cards, converter, import conflict protection, catalogue paging, SimpleBlog publishing/installation and Ajax composition.
- Existing 111 webinar source hashes still match the pre-batch backup. No audience, mail or booking mutation is part of this batch.

**Outstanding:** a signed-in staging editor save/reload browser test awaits permission to use the existing staging test-login file, or a user-provided signed-in session. Automatic approval review rejected copying that credential file without explicit permission. Public read-only checks used the already existing internal HTTP endpoint; no access gate was disabled and no credential was read.

## Before cutover

- Review representative imported articles, especially galleries, bird-sound embeds and external media.
- Complete the signed-in editor journey above.
- Original post source URLs are retained and known internal article/archive links rewritten. Configure and test old WordPress URL redirects at cutover; retaining a source URL is not itself a redirect.
- Decide how YouTube snapshots will be refreshed after migration. Configure an official API-based or other reviewed refresh mechanism separately if automatic updates are wanted.
- Staff Add/Manage webinar workflow and final mail/subscriber/cutover checks remain separate work.
