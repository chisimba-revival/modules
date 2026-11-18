# Semantic course blueprints

Assign Course, Section, Chapter, Page, Quiz, Description or Content using the
semantic palette (drag or click), or the selected node's Semantic role menu.
Roles are stored as node types; decorative icons and media purposes stay separate.
The central node must be Course. Save the map, preview the complete outline,
then choose a new canonical course code and create it.

Structural nodes supply titles only. Description branches supply course, section
or chapter introductions. Content branches supply page bodies. Tagged text uses
the node's note if populated, otherwise its title, and is escaped as plain text.
Ordinary notes, URLs and attachments never become course content implicitly.
Quiz nodes create inactive, empty formative MCQ quizzes and provider-backed page
placements. Their titles must fit the existing 60-character quiz title field.

Consecutive pages directly under a course or section get an explicit
container chapter, displayed in the preview. Top-level branches generate clockwise from the upper right: right-side branches
top to bottom, then left-side branches bottom to top. Within each chapter, saved
branch order determines page order. Untagged nodes are listed as excluded; a tagged descendant
under an untagged parent is an error, not silently promoted or lost. Unsupported
nesting and multiple descriptions are validation errors.

Creation is POST/CSRF protected, requires map-management and canonical course
creation permissions, and locks the saved map revision. A linked course also
requires canonical management permission. The resulting course is private and
unpublished. Its content modules and home blocks are provisioned in the same
checked transaction as identity, canonical groups, sections, chapters, pages,
quizzes, queued quiz-input snapshots and server-owned provenance. MCQ Tests adds the registered `tbl_mcq_quiz_input_jobs` table.

Provenance lives in the map's existing `sourcemetadata.courseBlueprint` namespace;
map save/import payloads cannot write it. Explicit content locks generation from
the first creation. Untouched empty outlines can be regenerated in place. A
snapshot change in course settings, sections, chapters, page bodies/placements,
quiz records/questions, enabled modules or home blocks blocks replacement and
records a permanent lock. This intentionally also protects manual structural
edits. Visiting the course alone does not change its fingerprint. Deleting the
linked course permits a new generation. Existing unrelated course codes are
never targets. Codes use at most 32 characters, matching canonical deletion.
If legacy deletion retained module rows, choose a new unused code; generation
will never silently revive those rows. Map changes never synchronise to a course automatically.

Failed creation and regeneration roll back all database writes. Creation does
not switch the editor's active course or index unpublished material. Native page
creation supports deferred filesystem/search effects for this orchestration.
Generated outline and native page identities use collision-resistant random IDs
because bulk creation can insert many records within one second.

## Coordinated update

Install the matching framework context change (`createContext`'s optional final
`switchContext` argument; context version 2.069), plus modules knowledgemap 0.95,
contextcontent 2.108 and mcqtests 4.193. Use the normal Module Catalogue update
for dependency/language registration. No production deployment is implied by
these source changes. The template ZIP exporter is outside this step.

## Verification

```sh
php knowledgemap/tests/course_blueprint_test.php
php knowledgemap/tests/graph_service_test.php
php knowledgemap/tests/knowledgemap_contract_test.php
node knowledgemap/tests/editor_test.js
python3 knowledgemap/tests/run-course-pdo.py /path/to/matching/framework
```

The PDO runner creates an isolated internal Docker network and tmpfs MariaDB,
uses real dbTable/context/map/page/quiz code and schema, and removes containers
on exit. Identity permission decisions and canonical group provisioning are
synthetic collaborators; no installed database or credentials are read. It
checks empty creation and regeneration, section descriptions, quiz placement,
permission rejection, stale revision, content/quiz protection, deletion reset,
retry behaviour, rollback and preservation of the editor session.

Browser verification uses the real template, assets and shared skin with synthetic
HTTP responses: pointer drag, menu selection, autosave, preview, confirmation,
Help, Escape/focus return, and desktop/390px layouts. It does not establish a
live-site deployment or full installed identity-service integration.

## Quiz input and the worker

Every Quiz must be an explicit child of a Chapter. It is placed after that
chapter's pages. Use a final chapter for section/course review quizzes; direct
Course/Section quiz branches are validation errors. Paste source text directly into
the Quiz node's Note. An empty Note creates an empty quiz. Older Quiz input
children remain readable (note, or title when empty), following the Quiz Note in
branch order, but the editor no longer offers them for new assignments. Input must total 100–300000 characters; overlong notes are rejected,
never silently truncated. Input remains distinct from page content.

Creating the outline atomically snapshots the input and queues the existing
inactive quiz. No AI call runs in the HTTP request. The existing
`mcqtests/scripts/run_chapter_quiz_worker.php` drains this queue as well as the
older chapter-preview queue. Each step processes one part of about 12000 characters,
without discarding source text. The Quiz inspector requests 10–100 bank questions (default 30), with a separate
quiz size (default 10) and fixed/per-attempt selection. Each worker call requests
at most ten questions; completed stems are passed to later calls to avoid duplicates.
Preview shows both counts and the mode. Very long input requires at least one
question per source chunk.
Questions are inserted together only after all parts validate, and remain inactive
for human review. The shared AI service owns configuration/provider requests;
there is no separate credential or provider stack.

The map links to private generation status after creation and on later visits.
Completed parts survive failed requests. Interrupted jobs require explicit retry,
with a possible duplicate-charge warning. Claims are atomic, late results are
discarded, and quiz edits/deletion or revoked author access cancel generation.
A source snapshot is immutable: editing the map cannot change a queued request.
Tests use a deterministic AI boundary and never make paid provider requests.

## Repeatable map maintenance

`knowledgemap/scripts/map_snapshot.php` is CLI-only and requires
`CHISIMBA_MAP_MAINTENANCE=1`. Arguments are `backup|restore MAP_ID ABSOLUTE_JSON_PATH`.
Backup is exclusive (never overwrites a previous archive), mode 0600, with a SHA-256
sidecar. It includes map metadata, all nodes/notes, relationships and access grants.
Files linked by URL remain at their existing locations; these are map snapshots,
not independent file-manager backups. Keep snapshots private: they can contain
private notes and public-link tokens. Restore checks the checksum and map owner,
restores in one transaction and increases the revision to reject stale editor saves.
Delete the generated test course before resetting a linked map. Restore does not
delete courses, send AI requests or modify any other map.

Generated banks retain chapter tags and source provenance. Fixed mode copies one
random draw into the test; per-attempt mode copies the pool and freezes each
learner draw, answer key and order in a durable paper. Questions carry one mark
each. Bank edits do not rewrite copies or attempts. Existing jobs retain their
original plans and existing quizzes are not migrated automatically.
