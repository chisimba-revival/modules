# Grasses of the Lowveld and Kruger

`grassguide` 0.001 is a site-administrator editorial module. It prepares the
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
escaped at rendering; editable prose is plain text. No remote URL is fetched
server-side. Existing Chisimba skin, icon and contextual Help services are used.

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

This is a tested source module, not an installed production release. Full Module
Catalogue installation in the intended deployment and its surrounding site shell
remain release checks. There are no framework changes in this implementation.

## Next integrations

Automatic iNaturalist selection/import, GrassBase enrichment, scheduled POWO
diff/review jobs, local file-manager uploads, regional expansion, public/mobile
API and versioned offline packages are subsequent work. No worker or schedule
is silently enabled by installing or importing this module.
