# Multiple Choice Generator

Standalone, private MCQ authoring for offline teaching. Local route:
`index.php?module=questionworkshop`. Registered under teaching/admin tools and
My workspace. Administrators and site authors may use it; each saved set belongs
only to its owner.

## Journey

1. Enter a title, paste source text or upload UTF-8 TXT/ODT, and choose 1–30 questions
   (default five). Save, then check the extracted text.
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

- Source: 100–40,000 UTF-8 characters; uploads at most 5 MiB. ODT parsing reuses
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
The internal module identifier remains `questionworkshop` so existing sets and links
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
