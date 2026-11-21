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
