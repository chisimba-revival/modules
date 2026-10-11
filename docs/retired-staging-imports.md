# Retired LearnTheBirds staging imports — 11 October 2026

The following one-off operators are retired. Their entry points now exit 64 on
CLI (HTTP 410 otherwise), before reading input, booting Chisimba or connecting to
a database. Their last implementations are recoverable from Git commit
41f294ec6; they must not be restored and run against a current site without a new
migration design and disposable rehearsal.

- audience/scripts/import-icegram-users.php
- webinar/scripts/import-icegram-upcoming.php
- webinar/scripts/preserve-icegram-draft.php
- webinar/scripts/import-full-archive.php
- webinar/scripts/import-youtube-channel.php
- simpleblog/scripts/import-wordpress-posts.php
- contentblocks/scripts/configure-publication-imports.php

These were restricted to migrate.learnthebirds.com and/or September export files,
fixed source identities, an editorial owner and temporary media directories. The
WordPress-to-Chisimba move is complete. These are not general recurring import
APIs. The YouTube operator was also a reviewed staging snapshot replacement,
not a supported synchronisation job. Existing recordings, subscriber records,
consent, webinars and imported articles remain untouched.

Keep the reusable import planning, media and content services and their tests.
Future imports should use those services and dbTable, with explicit destination,
source provenance, consent checks, dry runs, atomic writes and tested rollback.
Do not recreate direct MDB2/PDO driver calls in a script.

The reusable sitepages composition and payment contribution schema operators
remain supported. They now call dbTable-backed maintenance services and the
shared ChisimbaDatabaseSchema DDL boundary, including its transaction guard.
