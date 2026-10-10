# Events

Paid in-person events for Chisimba. Author: Derek Keats <derek@dkeats.com>.

An event is reusable public content. An occurrence owns its date, capacity,
immutable product price and **private** meeting point, directions and parking.
A booking is a purchaser/order snapshot; each attendee receives a separate ticket.
No account or marketing subscription is required to purchase.

## Installation

Install/update `payment-service` to **1.035**, then install `events` **1.000**
through Module Catalogue. The standard post-install hook registers the `events`
`manage` capability, verifies required uniqueness constraints and creates the
private signing key once. Updates preserve the key and all event records. Never
rotate the key casually: existing booking, ticket and waitlist links depend on it.
Back up the database and configuration together.

The module uses canonical Security, Payment Service, Communications, User Details
public biographies, Time and Date Service, contextual Help, and skin primitives.
It reuses Security's bundled local QR encoder; ticket credentials never go to an
external QR generation service. The legacy QR Creator uses an external endpoint
and is deliberately not used for private ticket credentials.

Only administrators and users explicitly granted `events/manage` can create
content. Non-administrator organisers manage their own events. Assigned arrival
helpers can scan, search the attendee roster and download an attendance list;
they cannot edit events or view purchaser emails/payment operations.

## Organiser workflow

1. Open `index.php?module=events&action=manage` and create the reusable description.
   Keep exact locations out of public prose and images. An optional host user ID
   displays that person's reusable **public** biography.
2. Add an occurrence: local start/end/booking deadline, IANA timezone, capacity,
   VAT-inclusive ticket price and VAT percentage. For example, enter `115.00`
   and `15` to calculate an included VAT component of `15.00`.
   The service stores integer minor units and creates a versioned payment product.
   This first release assumes two-decimal currencies such as ZAR.
3. Put meeting point, parking and directions in the private arrival fields.
   Those fields are never returned by public catalogue/detail/metadata payloads.
   Calendar exports intentionally omit them as calendar sharing is common.
4. Publish the event and share its permanent public URL. Copy promotional text,
   or use the device share menu. To record where bookings came from, optionally
   add `&utm_source=facebook` (for a Facebook post) or `&utm_source=newsletter`
   (for an email newsletter) to the end of the public event link. Ordinary links
   work without a label; never promote an event using a private ticket link.
   Repeat an occurrence to prepare another date without copying its bookings.
5. Use booking operations for attendance, check-in, communications, unresolved
   payment fulfilment and review moderation. Cancelling an occurrence immediately
   revokes its tickets and marks confirmed bookings as requiring refund handling.
   **Cancellation does not initiate a provider refund.** Complete refunds through
   the configured payment provider; verified callbacks record the result.

Configure `EVENTS_PAYMENT_PROVIDER` as `yoco` or `paystack` using existing
Payment Service credentials and its webhook endpoints. There is no automatic
fallback to the fake provider. Verify the provider's sandbox journey and webhook
configuration before offering a real event. Event products are excluded from the
ordinary payment catalogue: purchases must reserve event capacity first.

## Tickets and inventory

A booking holds places for up to 15 minutes. Occurrence row locks serialize
reservations, waitlist offers, fulfilment and check-in. Repeated form submission
uses the same booking key. Verified payment callbacks re-read the canonical
ledger, issue tickets once and queue an idempotent confirmation. Browser return
parameters cannot confirm payment.

A late successful payment is honoured only when capacity remains and the event
has not started/cancelled. Otherwise it becomes `refund_required`, without a
valid ticket. Refund, reversal and dispute callbacks revoke admission; duplicate
reversal callbacks and maintenance can recover interrupted fulfilment.

Individual ticket links carry a scoped HMAC capability. Arrival codes use a
separate scope and revision. Transfers replace the attendee, increment the
revision and invalidate the old link/code. Codes are stored hashed. Private pages
use no-store, no-referrer and noindex headers. Protect normal server access logs
as private data because capability URLs can appear in them. Downloaded files and
screenshots cannot be recalled; live admission still checks current status.

Download produces a self-contained HTML ticket with an inline QR; print supports
browser/device Save as PDF. Wallet passes and server-generated PDF attachments
are not implemented. Camera scanning uses native `BarcodeDetector` on supported
HTTPS browsers. Manual code entry and name search remain available. There is no
offline multi-device scanning; export the minimal attendance CSV before travel.

## Communications and maintenance

Use the existing Communications sender and delivery worker. Event code queues
messages; it never adds audience subscriptions or invokes a separate mail stack.
Run this command periodically (for example every five minutes) through the
installation's normal background-worker setup:

```sh
php /path/to/site/packages/events/scripts/maintain.php /path/to/site
```

Maintenance queues confirmations/retries, occurrence changes, reminders during
the final 24 hours, cancellation/refund attention and follow-up messages. It also
reconciles recorded payment states and offers released places to email-confirmed
waitlist contacts. Offers reserve one place for at most one hour. A purchaser can
claim an offer only with its capability and matching email address. An organiser
can run maintenance for one occurrence from its operations screen.

The maintenance command **does not deliver email**. The normal Communications
worker must run too. No scheduler or production deployment is installed by this
module. Queue and provider configuration must be verified before launch.

Checked-in attendees can submit one editable review per ticket after the event.
Private feedback cannot be published. Consented reviews require moderation;
hiding requires a reason, and organiser replies are supported. Follow-up resources
remain behind valid ticket access.

## Validation

From the modules repository:

```sh
php payment-service/tests/event_payment_test.php
php payment-service/tests/payment_service_contract_test.php
php payment-service/tests/payment_catalogue_contract_test.php
php payment-service/tests/contribution_service_test.php
```

For an explicitly selected **development** installation:

```sh
EVENTS_TEST_SITE=/path/to/site php events/tests/runtime_test.php
```

Runtime tests use real transactions and concurrent PHP workers but replace email
and provider calls with fixtures. They create synthetic records and delete only
those records/products afterwards. Coverage includes the last-seat race,
reservation/callback idempotency, group totals and tickets, private-data filtering,
capability scope, owner/helper permissions, transfer invalidation, repeated scans,
review consent, revocation, late-payment recovery and reserved waitlist offers.

`tests/browser_fixture.php --create` makes a labelled disposable browser fixture
without real payments or messages. It prints the fixture ID and URLs. Remove it
with `--remove ID` when finished. Never use the fixture script in production.

Later extensions include wallet passes, provider-initiated refund controls,
verified offline scanning, discount codes, direct social-account publishing,
advanced campaign reporting. These are not advertised as available
in the first release.

## Local verification on 4 October 2026

Verified on the PHP 8.5.11 development container: fresh installation, repeated
post-install/update with preserved records and signing capabilities, six InnoDB
tables and enforced uniqueness, the runtime suite including concurrent workers,
and all twelve templates. Payment core, catalogue, contribution, event dispatch,
Yoco and Paystack fixture tests passed. PHP/JavaScript syntax and whitespace checks
passed. Chrome checks covered public privacy, QR rendering, ticket download,
Help/Escape/focus return, phone-width layout and retained form values on errors.
Disposable event records and products were removed after verification.

Authenticated organiser browser actions await administrator sign-in; organiser
service operations and template rendering were tested separately. Real provider
checkout/webhooks, outgoing email delivery and physical camera scanning were not
exercised. No production deployment or background schedule was configured.


## Editor update and retained development samples (4 October 2026)

Events 1.001 adds the shared Host directory dependency. Choose an existing host,
or add one inline without losing the event draft. Names, biographies and portraits
belong to Host directory; published Webinar speakers are offered through its
adapter when Webinar is installed. Event promotional images remain event-owned.
Difficulty is a dropdown with a separate terrain/distance/age field. Old free-text
difficulty guidance remains editable. Default terms use EVENTS_BOOKING_TERMS;
per-event terms override it, and each booking retains its accepted terms snapshot.
Help explains optional source labels with Facebook/newsletter examples.

`tests/browser_fixture.php --create` creates an available date and simulated paid
ticket. `tests/sample_scenarios.php EVENT_ID` adds arrival, sold-out/waiting-list
and attended/review scenarios. Both use the explicitly opted-in runtime test
harness, mock payments and an in-memory mail outbox. These samples are deliberately
retained when requested; remove only the labelled event with the existing fixture
removal command. Shared host profiles are separate reusable records.

The retained local sample event is `d4d8dcbf898c0307c962c9e1799111af`, titled
“Disposable Events browser preview”. It has four dates and four simulated paid
tickets. It is synthetic and is not a real offer. The UI-created “Sample Nature
Guide” is also synthetic. The arrival sample has been checked in in Chrome.

Validation: real database and payment regressions, editor/host contracts and all
12 template renders pass. Chrome verified inline host creation, draft preservation,
field-specific gallery errors, save, Help, settings navigation and manual arrival
check-in. Live provider payments, email delivery and camera hardware are not
exercised by these fixtures. No production deployment is included.

### Usability revision (Events 1.003)

The organiser editor now follows tasks rather than schema order: event story,
host/location, practical preparation, then optional photos/resources/terms. A
sidebar explains the setup sequence, status and save actions. Save and add dates
preserves the event before navigating to the date/ticket stage. Date, ticket price
and private arrival instructions have separate sections; timezone and arrival
helpers use selectors. Short answers use appropriately sized controls. Shared
skin publishing layouts, disclosure cards and brief-answer sizing provide the
presentation; there is no Events-only CSS. An unsaved-change warning and validation
that opens optional sections support recovery. No automatic draft storage is claimed.

Authenticated forms use the shared Security 3.112 session-bound token fix: no
separate form clock expiry or eviction from activity in other tabs. Earlier tokens
may require one renewed submission with retained values during deployment.


### VAT percentage (Events 1.004)

Enter a VAT-inclusive ticket price and a VAT rate (0–100%, up to two decimal
places). Included VAT = price × rate / (100 + rate), rounded to the nearest cent
per ticket. The browser previews the result; the server independently calculates
and stores the canonical minor-unit amount. VAT is never added to the entered
price. Existing bookings retain their price/VAT snapshots. Legacy dates infer a
rate for display; an unchanged price/rate retains their original rounded VAT.


### Public listing and ticket team (Events 1.005)

The default Events page uses the shared publication-card grid used by Webinar,
with event photographs (or a placeholder), date badges, host, summary, broad area,
price and availability. Upcoming cards show the next future date, falling back
to an ongoing date; past cards show the most recent finished date. A recurring
event may appear in both sections. Dates use their configured timezone. Drafts
and private arrival information are excluded.

Ticket check-in team is separate from change notices. Assign existing accounts
on the date editor, save, then give team members the check-in link. Logged-in
users can also choose Events → Check tickets to see authorised assignments.
Assignment removal takes effect immediately; every scan/read checks current
permissions. Helpers cannot access booking operations or purchaser contacts.
No invitation emails are sent by assigning a helper. Change messages appear on
private tickets; the existing communications action queues links to the updated
booking. The new catalogue, recurrence ordering, helper discovery/revocation and
all 13 page templates have local automated coverage.

### Public listing and home-page block

The public listing is `index.php?module=events` (also `action=catalogue`).
It shows upcoming and past events using shared publication cards and featured images.
The registered **Events** block (`events/events`) uses the same cards, showing up to
three upcoming and three past events, with **All events** opening the full listing.
Add it through the site's normal block editor; a wide/main region suits the card grid.
It is safe on anonymous pages: only published events and their public details are read.
Private arrival information and organiser controls are not rendered by the block.


## Source handover — 10 October 2026

Events 1.010 and Host directory 1.000 are preserved together on the shared
`release/consolidation-20260930` branch so work is available on other computers.
This is a beta source handover, not production activation. The earlier local
validation evidence above remains historical; no live provider payments, outgoing
email, physical camera test or live Webinar host integration is claimed.
Before launch, complete those checks, register dependencies through Module
Catalogue, and configure the documented maintenance and delivery workers.
Authenticated long-lived forms depend on framework Security 3.112 or later.
The stored development samples are database records and do not travel with Git.
