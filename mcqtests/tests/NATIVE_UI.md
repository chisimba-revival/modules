# Native Discussion and MCQ controls — 17 October 2026

Discussion 3.036 and MCQ Tests 4.188 remove their remaining active jQuery
callers. Keep the framework's global jQuery setting unchanged until the
separate whole-site dependency audit is complete.

Verified on an isolated local PHP 8.5/PDO clone with automatic jQuery disabled:

- Discussion post editing, replies, ownership, assessment restrictions,
  revoked membership, stale revisions and expired-form recovery.
- Original-language edits preserve translations and the actual post author.
- Native numerical authoring persists answers/options/units atomically;
  an intentionally failed child insert rolls back the parent and answers.
- Standard learner MCQ entry, submission, marking and result display;
  learner authoring, exports and another learner's results are denied.
- Expired attempt forms retain answer drafts without persisting the request.
- Native question selection, unit radio controls and contextual Help/Escape.

Run ordinary PHP tests in both modules' `tests` directories, excluding files
named `*integration_test.php`; run the MCQ `*.cjs` tests with Node.
The new native integration tests require `CHISIMBA_DISPOSABLE_TEST=1` and
explicitly refuse any DSN except the isolated editor fixture. Never change
those guards to run them against a working site. Their synthetic rows are
removed in `finally`; the forced database failure is expected test output.

This is not a complete security certification of either legacy module.
Further review is needed for old attachment/translation mutation routes,
other question-authoring routes, timed/multipage attempts and advanced
matching/calculated/numerical learner grading. The latter was not verified
end to end in this batch. Do not present it as a revived assessment engine.
Warnings observed in shared login/profile/denial pages and Workgroup are
outside this module batch; the changed Discussion/MCQ paths were cleaned up.
Production deployment requires a separate release and smoke pass.
