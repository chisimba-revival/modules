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

## Quoted replies — 19 October 2026 (2.004)

Each server-chat message has Reply. Selecting it shows a cancellable author/excerpt
above the composer; cancelling preserves typed text. Confirmed sends clear the
quote, while failed sends retain it. Saved replies show one compact quote without
nested indentation. The HTML fallback supports selecting a reply before composing.
Unchanged transcripts remain mounted between polls so their actions retain focus.

The registered replies table stores only message/channel/target IDs. The shared
service checks target membership in the same authorised channel, resolves the
canonical author/body and limits excerpts to 300 characters plus ellipsis. Reply
references and posts are written under the same checked channel transaction.
Retry identity includes the reply target as well as text. History joins quotes
inside the same channel; it never accepts caller-supplied quote content.

REST API 1.006 accepts optional `reply_to` on the existing messaging send endpoint
and includes `reply` in history. Existing clients remain compatible. This release
adds the Reply control to the server interface; the installed Android UI is unchanged.

Validated: PHP lint; same-channel/cross-channel and changed-retry checks; canonical
API quotes; web fallback, escaping and expired-CSRF recovery; eight client tests;
browser select/cancel/send, retained text, quoted output, Help and 390px overflow.

Version 2.005 places the accessible Reply arrow beside the timestamp, using the
shared time-and-date service and site timezone. Dates use the registered language
format. The `presentation` local fixture checks date handling and duplicate course
plugin rows (requires the coordinated framework context lookup fix).

## Announcement chats — 19 October 2026

Messaging 2.006 uses the same scrolling bubbles, polling and plain-text composer
for announcement channels. Context staff may send context announcements; site
administrators may send site announcements. Readers have no composer or Reply.
Existing announcements retain their title as the first line of the message.
New messages require no title: the announcements module derives an internal title
for compatibility with its management screens. No email or external notification
is sent by this path. Announcement delivery/read ticks are not yet collected.

Announcements 0.415 owns `announcementchat` and its registered receipt table.
It writes the existing announcement and context tables in a checked transaction,
under a shared database lock. Idempotency includes actor, channel and submission
ID; changed retries conflict, and revoked publishers cannot replay old receipts.
All three tables must be InnoDB. Legacy announcement timestamps came from PHP
local `dbTable::now()`; the chat adapter normalises these without rewriting them.
New announcement messages use UTC storage and the site's display timezone.

REST 1.007 adds GET/POST `messaging/announcements`, sharing the same service.
GET applies the website's audience rules, including roles in the requested context,
returns the latest 50 messages and `can_send`; POST accepts scope (`context` or
`site`), context, submission_id and body. Existing discussion endpoints are unchanged.
Run `local_fixture.php announcement-send` before `mobile_api.py`; checks cover
repeat sends, conflict, parent rollback, role restrictions and revoked access.
The compact Help and status text decode language entities once before the view
escapes them, so apostrophes and ellipses do not appear as entity names.

## Sightings — 19 October 2026

Messaging 2.008 adds a private sightings channel to an existing context
conversation. Members send one JPEG and a plain-text caption through the shared
REST API (1.008). The mobile app prepares JPEG/PNG/WebP inputs, corrects orientation,
limits the longest edge to 2048 pixels and the upload to 750 KB, and stores an
account/server/context-isolated IndexedDB outbox. HEIC is explicitly unsupported.
The original is unchanged. A stable submission ID covers both caption and the
input-image digest; identical retries return the existing receipt, while changed
content conflicts. History is bounded to the latest twenty sightings. Web viewing
uses the current context and refreshes while visible; composing is in the app.

File Manager 1.116 owns immutable private-image storage under SECUREFODLER,
outside the web root, and independently checks JPEG dimensions/bytes before
re-encoding without metadata. No public File Manager catalogue URL is created.
Image bytes, history and receipt replay all recheck messaging membership/bans;
public course visibility never grants access. REST image responses are no-store.
The storage primitive is intentionally internal: its caller owns authorisation.

Uploads require their own checked transaction. A failed insert removes the file;
a lost HTTP response can safely retry. A process crash can leave an inaccessible
unreferenced file. The explicit CLI maintenance tool defaults to a dry run and
can remove up to 100 unreferenced images older than 24 hours per run:
`CHISIMBA_IMAGE_MAINTENANCE=1 php packages/messaging/tools/cleanup-images.php`
(add `--apply` only after review). It also drains committed deletion cleanup when
applied. It is not a scheduled job. Context 2.067 invokes the cleanup outbox after
outermost course-deletion commit; rollback never removes an image. Failed file
cleanup retains its receipt for retry. New tables require normal catalogue update.

Local checks: actual PDO storage, idempotency/conflict, metadata stripping,
malformed/oversized image denial, injected insert failure, nested transaction
refusal, deletion rollback/outbox, orphan dry-run/retention/removal; normal HTTP
login/upload, anonymous denial in a public course, revoked member history/image/
receipt denial. Synthetic fixture accounts, content and groups were removed.
Browser checks covered offline photo/caption persistence across reload, reconnect,
revoked-history clearing, orientation, narrow layout and Help/Escape. Android
build passed; native-device photo picker and upload acceptance remain a separate
verification step. No production deployment is claimed by these local checks.
