# Context conversations

Version 2.002 is the first local web implementation, tested on 18 October 2026.
Open `index.php?module=messaging` within your current Chisimba scope. There is no separate context selector; URL parameters cannot override the active scope. Stale forms are rejected if the scope changed while they were open, preserving unsent text. Current
context staff and administrators can start its discussion; current members can
read and send plain text. While the page is visible, updates are checked every five seconds without replacing the composer. Sending confirms in place; a lost response retains both the text and the same retry key. Without JavaScript, the normal form and Refresh link still work. Earlier messages retrieves
older history; marking displayed messages read does not acknowledge newer,
unseen messages. Contextual Help explains these steps.

Administrator and context announcements are read-only views of the existing
Announcements module. Publishing remains in that module and follows its existing
audience and permission rules. The optional scope filter requires announcements
0.414; older callers retain the default combined view.

## Boundaries

The shared `conversationpolicy`, `conversationservice` and dbTable-backed
`conversationstore` are the intended boundary for both web and later REST callers.
Reads, writes and duplicate receipts recheck current membership and bans. Writes
use POST/CSRF. Per-channel transactions serialize message ordering; unique
(channel, sender, submission) keys make identical retries return the same receipt.
A changed payload with the same key conflicts. Read positions only move forward.
The web form retains submitted text on validation/CSRF/service errors and warns
before leaving with unsent text. It is not a durable offline draft store: keep a
copy before signing out or allowing the session to expire.

The existing REST API 1.004 now exposes messaging contexts, bounded history,
text send receipts and read positions, all through this shared service. The
mobile app has an account/server/context-isolated draft and send queue and uses
the home screen's scope. No independent authentication layer was added.
Push notifications, direct messages, moderation UI and image attachments remain
future work. Ban checks are implemented, but administration is not exposed yet.


## Installation and legacy preservation

Install/update messaging and announcements through Module Catalogue. The four
registered `tbl_messaging_*_v2` tables require InnoDB. No schema is created during
ordinary requests. Fresh installation and repeat registration were tested locally.
Inventory old rooms/messages before any future deployment or migration. Legacy
history has not been mapped into the new discussion tables and is not displayed.
No legacy database tables are removed by this change.

Original executable chat code is preserved as non-executable text under
`docs/legacy-v1/`; the old default-data seed is `docs/legacy-defaultdata.xml`.
Prototype/frame chat routes are closed. Old blocks now link to conversations.
The tiny `chatdisplay` adapter supports the toolbar's `imParams`/`divShowIM`
callers; remove it after toolbar and buddies callers move to the conversation
entry link. It does not restore the old private-chat behaviour. The separate
paused Jabber `im` module is untouched.

## Local checks

`tests/policy_test.php` is a standalone PHP policy test. `tests/local_fixture.php`
and `tests/local_http.py` are explicitly for the existing disposable local Docker
runtime (`chisimba-php85-web`, `https://chisimba.test:8445/ch`). They must never
be pointed at production. Install the two modules first. Prepare the disposable
REST test identity using `dev-environment/tests/restapi-local/prepare.php` in the
container, which writes a private `/tmp/restapi-local-fixture.json`; do not print
or commit that file. The current REST API and native authentication are required.

Run `local_fixture.php prepare`, then `service`, `rollback`, `announcements`,
with `MESSAGING_LOCAL_TEST=1`, as www-data inside the container. Run
`python3 messaging/tests/local_http.py` from this repository on the host.
The service fixture expects a fresh channel: do not run it twice without cleanup.
Always run `local_fixture.php cleanup`, deactivate the disposable identity using
the existing REST fixture permissions helper, then run its cleanup helper,
including after failures. The HTTP helper restores membership after its negative
check. None of these helpers send notifications or contact real recipients.

Verified locally: membership/staff/admin boundaries; revoked and banned access;
CSRF and HTTP method enforcement; escaped text; concurrent duplicate receipts;
changed-payload conflict; history pagination; monotonic reads; transaction
rollback; announcement scope/audience/date filtering; normal installation and
repeat registration; PHP syntax and announcement regression checks. Browser
checks covered desktop and 390px layout, compact/full Help and Escape focus
return. The narrow canvas still has a small existing outer horizontal overflow;
the conversation controls fit and wrap within their card.

The shared skin now provides chat bubbles, own-message alignment, a bounded
scrolling transcript and a composer beneath it. This requires the coordinated
chisimba-reborn skin update in the runtime framework checkout.

Progressive chat behaviour checks: `node --test tests/chat_client_test.cjs`
(lost reply, confirmed send and changed scope). Polling is suspended in hidden
tabs and when viewing earlier history. It currently retrieves a bounded HTML
page; the mobile REST history endpoint retrieves the latest 50 messages.

## Server chat refinement — 19 October 2026 (2.003)

The shared publishing layout places conversation and composer beside a narrow
context/announcement navigation panel. Context labels resolve systext; the module
name is simply Conversations. Visible latest-history pages poll every three seconds,
retain drafts and scroll position, and offer New messages when reading further up.
Earlier-history pages retain explicit navigation/read controls.

Own-message receipts: one grey tick means saved on the server; two grey ticks mean
acknowledged by another participant's browser; two blue ticks mean another participant
has marked it viewed. These are aggregate group receipts (at least one other person),
not proof everyone has read. The existing mobile read endpoint contributes read positions.
Web acknowledgements are CSRF-protected POSTs under the same scope/access checks as
sending. Read acknowledgements require a visible tab and the bottom message in view.
A new registered deliveries table preserves the distinction from read positions.

Legacy catalogue indexes do not enforce unique declarations. Receipt writes therefore
use the service's channel lock, update existing positions monotonically and insert
only when absent. Reading uses MAX to tolerate historical duplicate positions without
removing data. Do not bypass the service lock when writing production receipts.

Validated locally: PHP lint, policy tests, web HTTP access/CSRF/revocation/duplicate
checks, existing mobile API regression, five JS send/scroll tests, aggregate receipts
and monotonic duplicate-safe reads. Browser: in-place send, automatic fixture reply,
blue tick, draft/scroll preservation, New messages, contextual/full Help and Escape,
390px single-column layout without horizontal overflow. No live test posts needed.
