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

Consecutive pages/quizzes directly under a course or section get an explicit
container chapter, displayed in the preview. Branch relationship order determines
the generated order. Untagged nodes are listed as excluded; a tagged descendant
under an untagged parent is an error, not silently promoted or lost. Unsupported
nesting and multiple descriptions are validation errors.

Creation is POST/CSRF protected, requires map-management and canonical course
creation permissions, and locks the saved map revision. A linked course also
requires canonical management permission. The resulting course is private and
unpublished. Its content modules and home blocks are provisioned in the same
checked transaction as identity, canonical groups, sections, chapters, pages,
quizzes and server-owned provenance. No schema change is required.

Provenance lives in the map's existing `sourcemetadata.courseBlueprint` namespace;
map save/import payloads cannot write it. Explicit content locks generation from
the first creation. Untouched empty outlines can be regenerated in place. A
snapshot change in course settings, sections, chapters, page bodies/placements,
quiz records/questions, enabled modules or home blocks blocks replacement and
records a permanent lock. This intentionally also protects manual structural
edits. Visiting the course alone does not change its fingerprint. Deleting the
linked course permits a new generation. Existing unrelated course codes are
never targets. Map changes never synchronise to a course automatically.

Failed creation and regeneration roll back all database writes. Creation does
not switch the editor's active course or index unpublished material. Native page
creation supports deferred filesystem/search effects for this orchestration.
Generated outline and native page identities use collision-resistant random IDs
because bulk creation can insert many records within one second.

## Coordinated update

Install the matching framework context change (`createContext`'s optional final
`switchContext` argument; context version 2.069), plus modules knowledgemap 0.92,
contextcontent 2.108 and mcqtests 4.190. Use the normal Module Catalogue update
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
