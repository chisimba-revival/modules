# Sights & Sounds assignments: practice and formative

Design recorded before implementation, 11 November 2026.

## Ownership

A course bank holds immutable questions. Publishing an assignment freezes its
question pool and rules. Existing 0.002 activities remain unlimited practice.
Assignments expose their mode and results to Gradebook; Gradebook owns weighting.
No migration changes existing questions, papers or results into graded work.

## Permission contract

Every route uses current persisted course membership. Site administrators and
course lecturers may manage the bank, publish assignments, view course results,
and grant an individual an additional attempt. Enrolled members may start/resume
their own attempts and read their own results. Other users have no access.
These are named capabilities over the existing canonical roles, not a new role
store. Delegation to assistants/custom groups is a later extension.

An additional attempt requires an eligible course member, a reason, and records
the granting actor and server time. Grants add capacity; they do not delete old
attempts or override assignment dates. Duplicate form submissions reuse the grant
request ID and cannot add capacity twice.

## Rules

| Rule | Practice | Formative |
| --- | --- | --- |
| Grade | Normally zero weight | Percentage, weighting in Gradebook |
| Attempts | Unlimited | One initial attempt + retries (default 0) + audited grants |
| Save an answer | Immediate check and feedback | Save only, no correctness oracle |
| Finish | Immutable completion | Immutable submission, server marking |
| Feedback | Accepted names and notes | Overall score after server confirmation; no answer key |
| Download | Includes answer keys for offline feedback | Never includes keys, notes, correctness or first-answer correctness |
| Result selection | Latest completion | Latest completion; all attempts retained |

Starting/downloading reserves an attempt. Interrupted downloads and unfinished
attempts always resume that reservation, even when a client requests a fresh set.
Only a completed attempt permits the next attempt, subject to the retry budget.
The server serialises reservation per account/assignment. Browser and app share
the budget; revision checks prevent silent overwrites when both edit the same run.
Old clients must explicitly advertise formative package support before reservation.

## Dates and offline policy

Optional opening and due dates are entered in the site's configured timezone and
stored in UTC through the shared time/date service. These dates govern this
assignment; plan dates are not independently applied by this module.
No new attempt can start before opening or after the due date. Already-reserved
attempts can resume and submit after the deadline. Late receipt is flagged for
teaching staff, with no automatic penalty or loss of work. The server's receipt
time determines lateness; the phone clock is never accepted as proof of finishing.
Retries of a confirmed submission retain its original receipt time and mark.

Offline use means current permission changes take effect on reconnection; saved
media cannot be remotely removed from a disconnected phone. Reused practice
questions may already be familiar to students. Formative is not a secure exam.
Summative/proctoring and automatic late penalties are deliberately deferred.

## Screens

Author: question bank -> Publish assignment (title, instructions, question count,
mode, retries, optional opening/due dates) -> assignment overview -> Results and
attempt permissions (attempt history, marks, receipt times, late indicator,
member selector and reason for an additional attempt).

Student: assignment list shows mode -> overview shows rules, dates, attempts used,
remaining starts and previous result -> Start/resume -> one-question player ->
Save answer (formative) or Check answer (practice) -> Finish -> confirmed result.

App: Refresh assignments -> mode/rules visible before download -> reserve/download
-> offline answer editing and local save -> Finish freezes all formative answers,
including the currently typed draft -> waiting to send -> Send results -> server
receipt and score. Local formative completion never implies a mark. Failed sends
retain the frozen payload; retries are idempotent. Account changes hide cached work.

## Acceptance

Verify unchanged practice; default zero retries; configured retries; resume/fresh
bypass prevention; concurrent reservation; grants and duplicate grants; foreign
course/account denial; no keys/feedback/correctness in formative packages or HTML;
no correctness returned from save; server recomputation independent of client
checks/scores; deadline boundaries and late receipt; duplicate/stale submissions;
Gradebook classification/results; offline restart, draft inclusion, sync and result.
Use only disposable fixtures on 8445, normal Catalogue upgrades and the existing
authorised source-mounted local setup. Preserve the user's real content.
