# Multiple Choice Generator

Standalone, private MCQ authoring for offline teaching. Local route:
`index.php?module=mcqgenerator`. Registered under teaching/admin tools and
My workspace. Administrators and site authors may use it; each saved set belongs
only to its owner.

## Journey

1. Enter a title, paste source text or upload UTF-8 TXT/ODT, and choose 1–30 questions
   (initial default ten, remembered for the login session). Save, then check the extracted text.
2. Explicitly authorise sending that source to the configured shared AI service.
   Generate grounded single-answer questions with four distinct options each.
3. Review and edit questions, options, correct answers and supporting excerpts.
   Save and confirm review. Source is preserved independently of provider failures.
4. Download a question paper or separate answer key as TXT or editable ODT.
   Downloads use the saved version. Unreviewed exports are marked as drafts.
5. Optionally enter a destination course and reopen the set. Import a reviewed
   snapshot as an **inactive MCQ test/question pool**. The existing course-question
   lookup can reuse those questions. Import is teaching-permission checked,
   transactional and idempotent for that set. It does not publish an assessment.
   Later workshop edits do not alter the imported copy.

Contextual Help explains each step, permissions and recovery, using the shared
Help drawer and Escape behaviour. UI uses existing skin, icon and language services.

## Boundaries

- Source: at least 100 UTF-8 characters, up to 5 MiB of text; uploads at most 5 MiB. ODT parsing reuses
  ingestservice with bounded expanded bytes/entries. Images are ignored. Review
  extracted lists/tables before generation. Original uploaded binaries are not kept.
- AI: existing mcqaigenerator/aiservice; no new provider integration or credentials.
  Default course behaviour remains five questions. Requested counts and source
  grounding are checked. AI accuracy still requires human review.
- Concurrent generation is claimed atomically; there is no automatic resend after
  interrupted processing. Failed provider requests require an explicit retry.
  A process killed while generating requires administrator investigation/recovery.
- Saved sets support Unicode. Supplementary characters in imported MCQ rich text
  are encoded as HTML character references for legacy UTF-8 tables. MCQ writes are
  read back before commit; failed writes roll back rather than producing a partial
  question pool. A legacy course-title storage failure is also rolled back.
- The old question-bank interface is not redesigned here. Backend course lookup
  and inactive-pool import are tested; its complete reuse/edit UI still needs a
  separate user journey review. Each set can currently import to one course once.
- No production deployment, scheduled job, or background provider retry is installed.

## Verification (18 September 2026)

Passed:
- 20 source, ownership, validation and export checks in `tests/workshop_test.php`;
  additional shared-generator checks for 1, 3, 5 and 30, invalid bounds, wrong
  response count and unchanged default five.
- Actual configured AI provider produced one grounded question from synthetic text.
- Installed local schema, source save/reload and selected count; edits and Unicode
  survive reload; all four downloads; answer-key exclusion from question papers.
- Chrome local author/other-author/student checks: owner downloads succeed,
  other author receives 404, student 403, invalid CSRF rejected. 390px layout fits.
- Help open/Escape; no JavaScript errors in the review/download run.
- Synthetic local import through real MCQ database services: teaching denial,
  three questions/four answers each, inactive status, repeated import returns the
  same test and course question-pool lookup finds all three.
- ODT ZIP/XML validation and shared-parser round trip. LibreOffice 26.2 opened
  and rendered the exported question paper. The older system launcher failed to
  load it; the installed 26.2 executable succeeded. Emoji display depends on fonts.
- Existing MCQ chapter generation contract: 18 checks pass.

Browser fixtures never call a paid provider. Run only against disposable local
Chisimba. Provision and seed scripts require QUESTIONWORKSHOP_LOCAL_TEST=1;
source_browser.cjs and review_browser.cjs use Playwright on NODE_PATH, local
chisimba.test:8445 and private /tmp/workshop-fixture.json. Sequence: provision,
copy fixture JSON to host, source browser, copy updated JSON into container, seed,
review browser, import_integration.php. Disable QA accounts and remove their rows
and private fixture/session files afterwards. Do not commit credentials or fixtures.

Owners can delete a saved set using the Delete set icon button beside Open set, then confirming the popup, including source-only sets.
Deletion requires POST, CSRF, explicit confirmation, ownership and the current
revision. Existing downloads and imported course copies are independent and remain.
The internal module identifier remains `mcqgenerator` so existing sets and links
continue working.

## Long-lived forms

Before each JavaScript-enhanced submission, the authenticated page obtains a fresh
single-use CSRF token from a POST endpoint requiring both a custom module header
and `Sec-Fetch-Site: same-origin`. It does not submit a mutation until renewal
succeeds, and never retries a submitted mutation automatically. Login redirects,
network failures and denied renewal leave all form controls and entered text in
place. The no-JavaScript fallback still checks the original token and preserves
submitted edits on token failure. Invalid form tokens are not described as expired
logins. Browser regression forces token eviction with 15 additional page loads,
then confirms the original form saves successfully. Separate browser checks cover
login-redirect failure, draft retention, duplicate submission and reduced motion.

Rejected AI responses now retain a private validation report with the saved set: question counts, malformed questions, duplicate options and unmatched source quotations. Reports survive reload and are cleared when another generation begins. No automatic provider retry occurs. Failures recorded before this update cannot be reconstructed because their responses were not retained.

The former questionworkshop route redirects read-only links here. Saved sets retain their original table and ownership. Old write forms are not replayed; reopen them at the new route.

AI responses that fail grounding checks retain bounded review candidates. Flagged questions start excluded; authors may correct or explicitly include them. All choices persist, excluded questions remain editable, and TXT/ODT exports and course imports use only the saved included questions, consecutively numbered. Included questions still require four distinct options and a valid answer. Original warnings remain visible after human review. Historical report-only failures cannot be reconstructed.

Whole chapters now accept UTF-8 source up to 5 MiB (at least 100 characters). Shared AI capacity metadata supplies the model context and output limits. Planning uses UTF-8 byte length as a conservative BPE token bound, reserves instruction/schema headroom and output tokens, and splits losslessly at paragraph boundaries where possible. Unknown models require explicit AI capacity configuration. Each section is a separately claimed browser POST, with persisted progress and no replay of uncertain in-flight requests. Up to 30 sections are supported; partial failures retain completed candidates with a coverage warning. No paid provider was used to test this path. Chapter 7 (88,722 characters) fits one request on the configured gpt-5.6 model.


## Exam question generator (0.4.0)

`action=exams` opens private saved exams; `action=examnew` creates an empty paper.
The normal generator header links to this workspace. No AI calls are made.
Select included questions from each saved chapter, then arrange their order, set
whole-number marks (1–100), instructions and optional chapter headings. Up to 200
questions fit a paper. Save a review confirmation before using the final exports.
ODT downloads use title-questions.odt and title-marking-sheet.odt, share consecutive
numbering and total marks, and keep question/options together where page space
allows. Drafts are visibly labelled. The marking sheet retains source references
and evidence; the candidate paper excludes both answers and evidence.

`tbl_mcqgenerator_exams` stores private, versioned JSON snapshots independently of
source sets. Each entry retains set id, source revision, question index and chapter
title for future chapter-aware bank integration. Deleting or editing a source set
does not change an exam. Same-set/question duplicates are blocked. Writes require
POST, shared renewed CSRF, ownership and matching version; truncated edit payloads
are rejected by an end-of-form marker. The chapter picker reads only metadata and
sorts chapter titles naturally. Unsaved navigation is guarded in the browser.

Course bank work is deliberately deferred. No exam route publishes or imports a
course assessment. Installation creates the new table through modulecatalogue and
leaves all existing sets untouched. Contextual Help covers the complete workflow.

Tests: `tests/exam_test.php` (pure service/export contracts) and
`QUESTIONWORKSHOP_LOCAL_TEST=1 php tests/exam_integration.php` (local disposable
DB records). Run existing workshop and section-processing tests for regressions.


## Additional questions (0.4.1)

Generated sets expose Add more questions. An append job atomically claims the
current revision, preserves original candidates and reports, and requests additional
questions with bounded existing-stem avoidance context. Source planning reserves
space for that context. Exact normalised stem repeats are omitted; semantic
similarity still requires human review. Existing question indices remain stable.
The total candidate limit remains 30. New questions clear the review confirmation;
a failed request with no additions preserves the prior review. Shortfalls and
partial failures are reported without automatically retrying provider requests.
Concurrent/stale saves cannot overwrite an in-flight job. Exam/course snapshots
remain independent. No provider calls are used by `more_questions_test.php` or the
local guarded `more_integration.php` persistence test.

New standalone sets default to 10. A valid count submitted on create/generate is
remembered in the existing Chisimba module session for subsequent sets. Additional
counts do not change that preference. The shared course MCQ generator keeps its
existing five-question default.

## Separate exam workspaces (0.5.0, 20 September 2026)

The module entry point now opens **My exams**. **New exam** saves a named,
empty workspace and opens its first chapter-source form. **Open chapters**
lists only that exam's question sets; **Assemble exam paper** selects only its
generated chapters. Explicit parent IDs in links/forms keep separate browser
tabs independent. Existing question-set URLs continue to work and resolve their
saved parent. Exam cards show chapter and assembled-question counts.

The registered `examid` column is added by the guarded pre-install hook. The
post-install hook transactionally attaches unassigned legacy sets to the owner's
oldest existing exam (or creates an `Existing exam` if none exists). It does not
rewrite source, questions, revisions, imports or existing exam snapshots. Repeated
updates leave assigned sets unchanged. Run both hooks through Module Catalogue;
copying source alone is insufficient. Back up existing database records first.

Chapter creation checks and locks the parent owner. Cross-exam picker requests
and additions are refused. An exam with chapter sets cannot be deleted; chapter
sets must be removed individually first. No bulk deletion or reassignment is
introduced. Source snapshots already assembled into papers remain independent.
The existing shared skin, icons, language system and contextual Help are reused.

Verification on the local PHP 8.5.4 installation:

- Syntax and whitespace checks; source/workshop, generation-section, append,
  exam assembly/answer mixing and chapter-count regression tests passed.
- `QUESTIONWORKSHOP_LOCAL_TEST=1 php tests/workspace_integration.php` passed
  real-database list/picker isolation, owner and missing-parent denial, safe
  deletion, Unicode and repeatable legacy migration with/without an existing exam.
- Existing real-database exam and append integration tests passed.
- Real Chrome: two disposable elective workspaces, empty starts, separate chapter
  sources, switching, source and paper save/reload, isolated question selection,
  both ODT downloads, rejected cross-exam GET/POST, invalid CSRF, retained invalid
  source input, other-author/student denial, Help keyboard opening and Escape/focus
  return, full guides, and four workspace screens at 390px all passed.
- Original 30 local chapter sets and one saved exam preserved; disposable records
  removed and test accounts disabled with teaching memberships revoked.
- Synthetic questions were used; no paid AI calls or production deployment.

## Live question totals and removal (0.5.1)

Chapter selection now keeps saved, newly selected and projected total counts in a
sticky summary beside Add selected questions. Review or remove questions jumps
to the editor without losing pending selections. The editor shows questions after
saving and pending removals, with a Save exam action kept visible while scrolling.
Each question has Remove from exam / Undo removal; the Include in exam checkbox
is also available. Saving applies removals to the paper only. Source questions
remain available to add again. Addition and edit totals describe their separate
save operations. The framework skin supplies `chisimba-sticky-summary`.

PHP 8.5 syntax, existing exam and count tests, and real Chrome checks passed:
selection/deselection counts, sticky summaries on desktop and at 390px,
remove/undo, removal save/reload, unchanged source and re-addition, checkbox
removal, in-page navigation preserving selection, and no JavaScript errors.
`tests/exam_selection_browser.cjs` uses a disposable fixture described by
`/tmp/mcq-counts-fixture.json` (teacher credentials, one exam and one generated
chapter with fifteen synthetic questions); it never calls AI.

### Question-card layout (0.5.2)

Order and Marks now share an aligned control row with the Remove/Undo action.
Compact card padding reduces whitespace. The checkbox is retained as a no-JavaScript
fallback and hidden once removal buttons are active. Pending-removal badges start
empty and appear only when a removal is pending. Reusable compact-card/control-row
primitives live in the framework skin. Real Chrome desktop/390px screenshots and
selection, remove/undo and save/reload checks passed, along with PHP 8.5 and existing
exam rendering/domain checks.
