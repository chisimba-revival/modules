# Grasses of the Lowveld and Kruger

`grassguide` 0.007 is a site-administrator editorial module. It prepares the
179-species Lowveld / Greater Kruger collection for a future field-guide app.
No account other than a signed-in site administrator can read the workspace,
export its data, or change its content. There is no public reading endpoint yet.

Install through the normal Chisimba Module Catalogue with security, language,
ui and help available. The three registered InnoDB tables and their indexes
are created by the shared catalogue/schema services. No schema creation occurs
during ordinary requests. Open `index.php?module=grassguide`, choose **Setup and
readiness**, inspect the missing names and select **Import missing species**.

The bundled `data/lowveld-kruger-v4.csv` is an unchanged copy of Derek's supplied
`sa_lowveld_kruger_grasses_A-Z-v4_POWO_Ecology.csv`. The broader master list is
deliberately excluded. Import records the file SHA-256 and original row in the
audit history. It adds missing records atomically; reimport never overwrites
edits and recognises renamed/archived seed records through their source key.
An existing manually created exact name is skipped. Duplicate current names
are prevented by a unique index. The input must match the bundled CSV schema.

## Content management

- Search scientific names, common names and synonyms; filter editorial status.
- Add/edit species with stable identifiers, botanical authorities, descriptions,
  distinguishing features, similar species, habitat and private notes.
- Record multiple inflorescence forms, awn state and evidence. Empty means
  unknown. These are broad initial filters, not a complete botanical key.
- Retain imported ecology verbatim and separately record review/evidence. The
  CSV's `Invader` value is a legacy class, not a checked legal invasion status.
- Record POWO IDs, check dates and source notes. Imported Accepted values are
  explicitly *reported checked*, without inventing validation dates or IDs.
- A reviewed profile needs identification text plus reviewed taxonomy/ecology.
  Review is an editorial marker, not publication. Renames preserve the previous
  scientific name as a synonym and reset taxonomic review.
- Add/edit/archive image references with diagnostic view, source page, creator,
  exact licence, caption, alternative text and permission notes. Saved images
  are previewed directly by the browser with no referrer; the server does not
  fetch URLs. Failed/broken third-party images do not remove their metadata.
- Record YouTube IDs and playback links. The module does not download video.
- See the latest 20 audit events; complete content snapshots stay in the database.
- Download an administrator-only JSON editorial export (schema version 1),
  including drafts, archived records, notes and media references. This is not a
  public release, a restorable database backup, or an offline media package.

Species, media and audit repositories use real `dbTable` checked prepared calls.
Each save and its audit are transactional. Revisions reject stale saves and
replayed creates; uncertain network writes are not automatically retried.
POST and the canonical session-bound CSRF service protect mutations. HTML is
escaped at rendering; editable prose is plain text. Only the background provider workers fetch allowlisted public API URLs server-side. Existing Chisimba skin, icon and contextual Help services are used.

The PHP/JavaScript module code follows this repository's GPL terms (`../COPYING`).
Media retains its own licence; the code licence grants no rights over photographs
or YouTube content. No third-party media is bundled. The initial CSV is supplied
editorial material, with ecological sourcing/review still pending; it must not
be represented as a newly validated Kew dataset.

## Verification

Run from the modules repository, with sibling `framework`, Docker access and
the existing local `chisimba-php85-web` / `mariadb:10.11.18` images:

```sh
python3 grassguide/tests/run.py
python3 grassguide/tests/run.py --preview
```

The runner creates an isolated internal Docker network and temporary MariaDB
database. It uses the real framework PDO connection, dbTable and schema services;
only object composition and the user identity are fixture substitutes. It never
uses an installed site's database or credentials. The preview serves the actual
controller/templates, skin, icons, Help and CSRF service on loopback port 8768.
Its PHP container also joins Docker's bridge so the published loopback port works;
the database stays internal. Ctrl-C removes the fixture containers and network.
Do not deploy the test router as a site or mistake its synthetic admin for real
authentication. Tests modify synthetic profiles, including a `Testus fixture`.

Verified on 21 November 2026: 179-row import/replay, administrator and anonymous
denials, exact Unicode persistence, stale saves, rename preservation, reviewed
evidence requirements, cross-species media denial, image/video validation,
archival, transaction rollback on audit failure, private export, controller
method/CSRF enforcement, HTML escaping and Help permission checks. Browser
verification covers search, saved content, validation preserving entries, Help
Escape/focus return and a 390px editor without horizontal overflow.

## Automatic enrichment

The module is installed for local development at `https://chisimba.test:8445/ch/`.
Setup → Fetch available data creates durable per-species jobs for Kew's documented
species data portal, GBIF, Wikipedia, iNaturalist and the cached GrassBase release. The local worker also queues
new species and refreshes results older than 30 days. No external request runs in
a page load. Edit is maintenance: imported data is immediately available in the
editorial export without requiring a human review marker.

Kew exact accepted species matches supply identifiers, authorities and synonyms.
GBIF supplies exact-match licensed English descriptions where present. Wikipedia
supplies attributed, revision-linked description and habitat sections. Only
explicit, unambiguous inflorescence phrases become filters; unknown traits remain
empty. iNaturalist supplies up to six community-selected taxon photographs with
photographer credit, source and licence. Noncommercial licences are allowed;
unlicensed and NoDerivatives photographs are excluded. Photographs are remote
references, not bundled image files. Views are marked unclassified until evidence
or an editor identifies the diagnostic part. Code and third-party content retain
separate licences, including attribution/share-alike/noncommercial conditions.

Field sources, fetched timestamps and last imported values are stored with each
species. Refreshes update only unchanged fields previously owned by that source
or vacant fields. Saving a human change locks that field, including deliberate
clearing. Renaming never happens automatically. Jobs store their mapped responses
as a cache; audit and content writes commit atomically. Duplicate jobs/images are
idempotent. Provider workers use database-scoped locks, bounded HTTPS GETs, a
1.1-second delay before every request, and provider-wide Retry-After cooldowns.
Timeouts/5xx retry with exponential backoff; 401/403 pause the provider for a day.
After four failures a job stays failed for operator investigation. No-match results
are visible and cached rather than guessed. Missing similar-species advice, videos
and private notes do not prevent a useful profile.

From an installed application root:

```sh
php packages/grassguide/scripts/run_enrichment_worker.php queue
php packages/grassguide/scripts/run_enrichment_worker.php kew 20
php packages/grassguide/scripts/run_enrichment_worker.php gbif 20
php packages/grassguide/scripts/run_enrichment_worker.php wikipedia 20
php packages/grassguide/scripts/run_enrichment_worker.php inaturalist 20
```

Run one loop per provider through the site's service manager, or invoke bounded
batches periodically. Different providers may run concurrently. Same-provider
concurrency is rejected by the shared database lock. Interrupted jobs resume on
the next invocation. The local Docker worker polls every 60 seconds, using the
same published read-only source bundle as the web container. It requires no API
keys. Keep this process in deployment stop/start and source-verification checks.

Upstream schemas and content coverage vary. Kew POWO's old undocumented endpoint
returned HTTP 403 during implementation; it is not scraped or bypassed. Its working
documented species portal currently supplies taxonomy but no botanical prose.
The app does not claim API coverage for every optional editor field, or substitute
unverified genus-level information for a species profile.

Provider documentation: [Kew](https://species-ws.data.kew.org/openapi),
[GBIF](https://techdocs.gbif.org/en/openapi/),
[iNaturalist](https://www.inaturalist.org/pages/api+recommended+practices),
[MediaWiki extracts](https://www.mediawiki.org/wiki/Extension:TextExtracts).

Additional integration tests cover queue deduplication, interrupted recovery,
Retry-After propagation, exact-match rejection, provenance, manual overrides,
licensed-photo filtering/replay, and rollback. Public/mobile API, offline packages,
local uploads, optional video discovery and regional expansion remain later work.

## GrassBase regional cache

The freely licensed dataset [Vorontsova et al. (2026), GrassBase](https://doi.org/10.5281/zenodo.22302582)
(CC BY 4.0) supplies botanical descriptions and structured morphology. Original
2016 description authors: W. D. Clayton, M. Vorontsova, K. T. Harman and H. Williamson.
The module ships a regional subset, with dataset DOI, original/accepted source
names, archive paths, character values, input file checksums and SHA-256 hashes.
It contains 165 unambiguous matches to the 179-entry catalogue; unmatched names
are explicit. A source taxonomic concept may use a different accepted name:
match type and both names are retained, without renaming the local catalogue.
No fuzzy matches or older synonym-lumping assumptions are used.

Rebuild from the public Zenodo records API (large inputs stay outside the repo):

```sh
python3 grassguide/scripts/build_grassbase.py --cache /path/to/grassbase-cache
```

The worker imports this cache without repeating bulk downloads. A new dataset
version requires rebuilding and reviewing the versioned cache; the 30-day queue
refresh does not imply that the pinned GrassBase release is newer. The complete
source table has an unlabelled row-name column, checked explicitly by the builder.
Alternative DELTA states (`/` and `&`) are retained as multiple filter values.
Character 260 maps panicle forms, 315 maps raceme disposition, and 213 marks
compound spathe-bearing inflorescences. Character 870 describes fertile-lemma
awns; mucros and pungent tips are not silently turned into awns. Implicit/default
states in the source are preserved and identified in the reference text.
GrassBase takes priority over unchanged imported Wikipedia/GBIF botanical prose;
manually maintained fields always win. Broad filter categories are identification
aids, not a definitive species key. Habitat and similar-species text remain unknown
where no suitable source provides them.

## Rights audit and publication (0.008)

Rights and publication is administrator-only, as are every task, setting and
catalogue download. The default noncommercial policy keeps licensed NC images
available for the free edition. No public feed or payment service is installed.
The audit derives current images and source-backed fields without API calls or
queue writes, shows commercial alternatives, and prioritises NC-only species.
Tasks record assignee, target date, progress, replacement URL and private notes.
Overdue tasks can be filtered; no email or scheduled notification is sent.

The normal catalogue installer creates `tbl_grassguide_rights`. Settings and
per-content tasks use dbTable, revision checks, checked transactions and audit
records. An exclusion always wins. Progress is not a rights grant. Explicit
commercial permission requires private evidence and a public credit, and is bound
to a hash of the content and its source. Refresh timestamps alone do not invalidate
it; changed images, text, creator, licence or source do. Worker imports never edit
these decisions. Archived content does not appear in active tasks or app exports.

Download app catalogue uses the saved policy; Preview commercial catalogue is
read-only. Commercial mode removes NC and unresolved media/fields unless current
commercial permission is documented. This conservative check leaves taxon names
and stable IDs, but may leave other fields blank. It is recorded-licence triage,
not a legal certification. Noncommercial mode retains current seed/prose fields
with incomplete rights metadata for review, but does not grant unknown images
publication permission. Permission-only/YouTube references require an explicit
clearance; source links and private editorial records remain available to admins.

App JSON uses the existing `lowveld-field-guide` v1 shape, with publication mode
and omission counts. Private notes, assignment details, evidence, jobs and audit
identities are excluded. Public permission statements accompany cleared content;
they do not relicense the original work. The private editorial export is unchanged
and is never a public app release. No endpoint allows anonymous or learner access.

Trusted operators can run `php packages/grassguide/scripts/export_app_catalogue.php`
from an installed site; this CLI-only path uses the same saved policy. App build
scripts must delegate to it instead of copying the editorial data directly.
Changes reach a phone only through a later bundle/update process.

Version 0.009 adds opt-in public updates at `action=catalogue`. The existing
administrator publication form enables them explicitly; new/existing sites stay
disabled until saved. Only the saved-policy app projection is exposed, with stable
content ETags, no-store responses and credential-free CORS for Capacitor/browser
clients. GET/HEAD/OPTIONS only; private admin exports and all mutations retain
login, persisted administrator and CSRF checks. Disabling updates returns 404 even
for a matching ETag. Downloaded offline copies cannot be remotely recalled.

Version 0.010 keeps image audit and replacement work in the private administrator
workspace. Shared post-login administration navigation occupies the right sidebar;
form cards fill the main column. The rights page shows image thumbnails, active
photo task counts and filters for priority/no alternative, planned and resolved
work. Resolution never grants reuse rights. No task/audit data is added to the
public app feed. The mistakenly added mobile audit was removed in app 1.3.

## Field profiles (0.011)

GrassBase workers cache four concise highlights for each of the 165 matched
species. Explicit character states and original numeric ranges are retained;
implicit, missing and qualified states are not turned into confident summaries.
Selection compares known characters among regional genus peers, or similar
head forms where no genus peers exist. These highlights are not diagnostic keys.
Administrators can edit Key features (one feature per line); manual edits and
deliberate clearing survive refresh. Existing completed GrassBase jobs without
highlights are queued once, preserving provider cooldowns.

The public bundle includes an allowlisted GBIF taxon identifier only from an
exact current-name successful cached match; provider responses remain private.
The phone uses this for online occurrence maps. The iNaturalist taxon-photo
API does not supply descriptive captions: observation descriptions are not
substituted for photo captions. Existing editorial captions remain supported.

Checks: `python3 tests/key_features_test.py` and the isolated PDO suite.

## Catalogue membership (0.012)

Add species opens a short form requiring genus and epithet only. Common name is
optional; the running worker discovers new records and fetches available data.
The pinned regional GrassBase cache still covers its documented seed subset;
new names outside it may receive descriptions and photographs from other sources.
No missing traits or key features are invented.

The catalogue defaults to active entries. Remove from catalogue opens a named
confirmation, then archives the species through a revision-checked, CSRF-protected
POST. Archived entries remain searchable with their media and history intact.
Restore to catalogue returns the same ID as a draft. Seed import does not undo
removal. The public feed excludes archived entries; existing phones receive
changes on their next successful catalogue update without reinstalling.

## NC contributor contact leads (0.013)

Rights and publication → NC image contributors groups active NC image entries by
exact credited name, with source pages, species, original licences and current
commercial-clearance counts. Names are contact leads, not verified owner identities.
Archived species/media are excluded; NC originals with recorded permission remain
visible. CSV exports one row per image entry, not per unique person or image URL,
and guards spreadsheet formulas. Both page and export require an administrator;
private task notes/evidence and contact details are not exported. No contact
lookup, API calls or messages are performed. Existing per-image permission records
remain the place to record permission scope, evidence and public credit.

## Contributor permission requests (0.014)

The private directory adds a contributor/count selector, image popup and saved
email drafts. Contacts and immutable request snapshots use the existing versioned
rights repository; no new table is required. Saved drafts include a personalised
opening and editable standard request. Preview enumerates current NC image entries
without recorded commercial clearance. An explicit POST queues the exact saved
snapshot through the shared communications service and configured verified sender.
A revision write, outbox insert and request audit share one database transaction.
Fingerprint checks reject changed drafts/images; request IDs make repeats
idempotent, and another pending request prevents a new queue entry. History reads
status from the shared outbox API. Provider acceptance is not inbox delivery or
permission. Replies are handled outside the app and permission evidence remains
per image. No contact details or correspondence enter the public feed or CSV.

Version 0.015 orders contributors by active NC image-entry count descending, with
alphabetical ties and retained zero-count contacts last. The default request adds
the approved sharing paragraph and simpler permission wording. Saved drafts and
queued snapshots are preserved; Use updated standard request replaces only the
draft body, retaining the email, personal opening and signature, and requires Save
before queueing.

### Species photo batches (0.016)

From a saved species, choose **Upload photos**. Select 1–10 JPEG/PNG/WebP images
(up to 10 MiB each, 40 MiB total, 24 megapixels and 8192 pixels per side), then
supply one caption per line in the numbered preview order. Commas, pipes and
quotation marks are ordinary caption text. Caption fields support Italicise
selection and a preview; single asterisks mark explicit italics. Credit and CC BY / CC0 / CC BY-SA apply to the batch.
Images retain their original bytes, including any embedded metadata; prepare
web-sized photographs before uploading when appropriate.

The optional **Replace all NC images and stop importing NC images for this
species** choice archives active NC images in the same transaction as the batch
and sets a persistent species import block. Other images and videos are retained.
Provider results check the latest block under the species row lock, including
results fetched before replacement began. Editing the species and later additive
uploads preserve the setting. **Allow future NC imports again** reverses the block;
archived images are restored only through an explicit media edit.

File Manager owns storage/catalogue registration. Files are privately stored and
served through grassguide's guarded photo action: administrators can inspect
archives; anonymous requests require inclusion in the current published app
projection. Generic file routes cannot expose module assets. Existing apps accept
these HTTPS image URLs; no rebuild is required. Public projection omits storage IDs
and import settings. Batch failures compensate unlinked File Manager files and
roll back media, archives and species state. Optimistic revisions reject stale
retries rather than creating duplicate batches. After an uncertain network result,
reload and check the species before retrying.

Run `GRASSGUIDE_FRAMEWORK=/path/to/current/framework python3 grassguide/tests/run.py`
against a framework with File Manager's `modulefilestore::storeMedia` contract.
The isolated suite covers real stored bytes, rollback/cleanup, permissions, line-caption
mapping, archive/block/resume and worker merging. With `--preview` running, use
`python3 grassguide/tests/http_photos.py` for the HTTP byte delivery contract.

### Premium checklist (0.017)

The public catalogue is unchanged. Admin → Grass app premium creates/revokes
per-device scoped Security API keys only when the account has an active
Entitlement Service grant (`premium`, `app`, `lowveld-grasses`). Admin status
alone grants no premium. `scripts/grant_premium.php --username=… --reason=…`
creates an idempotent lifetime founder grant through the canonical service.
Codes are shown once; never place them in URLs, logs or public builds.

`premiumstatus` verifies a scoped key and returns a maximum 30-day offline lease.
`checklistsync` accepts authenticated JSON POSTs; ownership comes only from the
credential. A per-account transaction merges operations and per-device sequence
numbers suppress lost-response retries. Last synced change wins for the same
species; different species merge. New registered checklist storage is private
and excluded from the public catalogue. No payment/signup flow is enabled.

Run the isolated test harness with `GRASSGUIDE_FRAMEWORK` pointing at the shared
framework and `GRASSGUIDE_SHARED_MODULES` at a checkout containing entitlement
and account-event services. Tests never send email or touch live grants.

### Image review and observation grades (0.018)

Flag incorrect image opens the media editor with the excluded state selected.
A private reason is required. The record stays as a per-species/photo tombstone,
blocking future imports even across image-size URL changes. Active restores it.
Media writes and worker merges share the species lock; manual edits preserve
provider/grade provenance. Exclusions are absent from public/commercial exports.
Older apps pick up removals through catalogue refresh; offline copies cannot be
remotely removed before reconnecting.

The paced iNaturalist worker matches exact photo IDs against up to 100 most-liked
observations (four pages of 25, including all quality grades). This is a bounded
lookup, not an exhaustive photo index: unmatched or ambiguous photos show Grade
unavailable. It refreshes grade metadata without replacing editorial captions
or invalidating rights permissions. Source observation IDs/grades and retrieval
time are retained; no observation location, user details or comments are stored.
A newly installed version queues missing grade lookups through the normal setup
action and worker. API errors retain previous data and use existing retries.

Species editors can select multiple images and use **Remove and do not reload**. This retains exclusion records to block provider re-import, preserves credits and provenance, and does not require replacements. Own-photo uploads remain independent; individual Edit can restore a removed image. Batch writes are atomic and check every selected image revision.

Premium media (0.021): the public feed omits videos and premium-only photos. The authenticated premium feed applies commercial rights eligibility and includes video links. Admin uploads and individual image editing offer Everyone/Premium only, defaulting to Everyone. File Manager serves premium-only uploaded bytes only after a current account entitlement check; external photo/YouTube URLs retain their own host access rules. No local sightings or community contribution features are included in this release.

### Private project/trip sightings (0.022)

Authenticated premium clients POST up to 100 sightings (maximum request 64 KiB)
to `sightingssync`. Each immutable record has a unique ID, species, project/trip
name, recorded time, notes and optional coordinates/accuracy. The existing
account checklist payload stores sightings under the same account row lock;
new records atomically add their species to the user's main checklist. Retry IDs
are idempotent; differing content under the same ID is rejected and rolls back
the entire batch. The server returns only that credential owner's records.
Coordinates never enter public exports or worker/catalogue records. Free,
revoked and wrong-scope keys cannot read or write sightings. No schema upgrade.
The app provides contextual Help; first-version records are append-only.

### Botanical typography and copyright uploads (0.023)

Similar species, Habitat and Ecological references have an Italicise selection
button and live preview. Single asterisks mark explicit italics in otherwise plain
stored text. Catalogue scientific names, genera and abbreviated binomials are
formatted automatically in displayed prose, captions and scientific-name labels.
Authors remain upright; subsp./ssp./var./f. rank abbreviations remain upright even
inside explicit italics. HTML input is escaped. Native input/select values and
plain-text exports remain text; no stored species/content migration is performed.

Photo uploads support © All rights reserved. The administrator must confirm
ownership or permission covering the free and paid app and supply a private
permission note. The batch stores the original copyright designation plus a
fingerprint-bound rights permission and © photographer credit through the existing
Rights and publication service, in the same transaction as the upload. A failed
permission write rolls back the batch and removes new files. Copyright without
recorded permission remains unpublished; later content changes invalidate the
permission as before. Permission evidence is never exported to public clients.

### Copyright edits (0.028)

Saving an active image as Copyright now requests the same ownership confirmation
and private permission note as batch uploads. Media and its current permission
are saved atomically, retaining copyright and respecting the selected audience.
Archiving or excluding an image does not require a new grant. The PDO fixture
checks missing confirmation, private evidence, both publication profiles and rollback.
