# Question Generator

Private, source-grounded question sets and exam papers for administrators and
instructors. Question generation uses the shared AI service; exam assembly,
review and downloads do not call AI. Source text, questions and answers remain
private to their owner. Existing course imports remain independent.

## Build an exam

1. Create a named exam, or open an existing exam from **My exams**.
2. Create chapter sets using **Multiple choice** or **Short answer**, then generate
   and review their questions. A saved MCQ set also offers **Create short-answer
   set from this source**. This reuses its source without altering its MCQs.
3. Open **Assemble exam paper**. Choose **Add multiple-choice questions** or
   **Add short-answer questions** directly in Section 2, open a chapter set and tick the desired
   questions. Select **Add selected questions** to include them explicitly.
4. The paper is organised into **Section 1: Multiple choice.** (1 mark each) and
   **Section 2: Short answers.** (2 marks each). Reorder questions within their
   section, review the counts and marks, and save.
5. Download the question paper and marking sheet separately as ODT files. Both
   use the same saved question order, continuous numbering and fixed marks.

The chapter picker lists *available sets*. A set's presence does not mean its
questions are included in the exam. Every chapter button shows its saved included count, including zero; positive
counts are green. Counts belong to the displayed question type and source set,
so switching to short answers does not display the MCQ counts. Hover over a badge
for its explanation. Generating
or saving a set never adds its questions automatically. Included source questions
can be selected once per exam; excluded questions must first be reviewed in their
source set. Only sets belonging to the current exam are offered, filtered by type.

Removal is pending until **Save exam**; **Undo removal** restores it before saving.
Removing a paper question does not remove its source question. Source changes do
not change saved exam copies. Save edits before switching sets or types. Paging
replaces only the chapter picker and preserves pending selections and exam edits.
Concurrent edits are rejected rather than overwriting another saved revision.

Question papers exclude answers, marking points and source evidence. Marking
sheets retain the answer/evidence and use a two-mark rubric for short answers.
Legacy per-point numeric allocations are omitted from the exam's criteria display;
the original source rubric remains stored. Review the marking guide before use.
New AI-generated short answers are designed for two marks. No uncertain or failed
AI request is automatically repeated. The maximum is 30 candidates per set and
200 questions per exam.

Contextual **Help** explains selection, sections, removal, downloads and recovery.
Shared skin action rows give text and Help buttons consistent heights, including
narrow screens.

## Rename and installation (1.0.0)

The canonical module and route are now `questiongenerator`. Install/update it
through Module Catalogue, including its registered installation hooks, then
update `mcqgenerator` to 0.7.0 and `questionworkshop` to 0.3.0. Both older modules
are hidden, read-only compatibility routes. Old write forms and paid generation
requests are never replayed: reopen the new page before submitting.

Keep the legacy modules installed while bookmarked links use them. Their removal
can be considered only after callers and bookmarks have migrated. The active
implementation exists only in `questiongenerator`; no duplicated service code is
maintained in the compatibility modules.

Back up the database and source before upgrading. Data remains in
`tbl_questionworkshop_sets` and `tbl_mcqgenerator_exams`, preserving IDs, owners,
source text, questions, answer ordering, exclusions, timestamps and revisions.
The post-install hook transfers **catalogue table ownership**, not the data,
from the old module to `questiongenerator`. It is repeatable. Never uninstall the
old data-owning version as a rename strategy. Upgrade it after the new module's
hook has transferred ownership. Existing language keys and CSRF scope names are
retained for compatibility.

Existing snapshots are grouped and allocated 1/2 marks when viewed or exported.
The upgrade does not rewrite them. Saving persists the new order/allocation;
a changed allocation or order requires review again. Existing included/excluded
questions are preserved; no short answers are inserted automatically.

Deploy the coordinated shared-skin `chisimba-form-actions--equal` rule with this
module. The module has no independent worker; question generation is an explicit
web action using the shared AI service.

## Verification

Run PHP syntax checks and the focused scripts in `tests/`: `workshop_test.php`,
`exam_test.php`, `exam_counts_test.php`, `section_structure_test.php`,
`short_answer_test.php`, `more_questions_test.php`, `section_processing_test.php`
and `legacy_route_test.php` in the old compatibility module. They use synthetic
content and fake providers, with no paid calls.

Database/browser tests require an isolated installation, its own database and
explicit `QUESTIONWORKSHOP_LOCAL_TEST=1`. Never point fixtures at a user's working
database. Check normal upgrade twice, unchanged saved-data checksums, old-link
redirects, ownership and revision conflicts, MCQ-plus-short-answer selection,
remove/undo/save/reload, section totals, both ODT exports, Help/Escape, consistent
button heights and mobile overflow. Remove the disposable installation afterwards.

Answer-position mixing is shown within Section 1 when MCQs are present and only
shuffles multiple-choice options. Short answers have no option-mixing control.
Save pending exam edits before following a section’s Add questions button.
