# Webinar registration limits and reminder dashboard

Run `python3 webinar/tests/run-capacity-tests.py` from the modules checkout (or
use its absolute path). Requires Docker, the local `chisimba-php85-web` image
with both PDO/MySQL and mysqli, MariaDB 10.11.18 and the sibling framework checkout.
The runner creates an internal-only network and disposable tmpfs database,
then removes them in `finally`. It never connects to an installed site or sends mail.

The suites use the actual dbTable, audience repositories and communications
outbox with synthetic addresses. They cover both PDO and retained MDB2:

- Simultaneous claims for the last place, in verified and immediate modes.
- Duplicate bookings, current limit under a row lock, cap changes, pending expiry,
  consent revision, confirmation rechecks, transaction ownership and rollback.
- Separate webinar-day subscriber and attendee emails, with a joining link only
  in the registered-attendee message; stable keys prevent duplicate sends.
- Reminder controls, SAST timing, configurable lead time, pre-delivery disabling,
  admin permissions, stale saves and aggregate pending/queued/sent counts.
- Repeated Audience installation hooks, subscriber editing, consent history,
  newsletters, cancellation and private count visibility.

Standalone `*_test.php` scripts additionally cover editorial validation, archive
selection, newsletter content/thumbnails and registration delivery policy. Set
`CHISIMBA_FRAMEWORK_ROOT` to the framework checkout for the thumbnail test.
Run with `framework/tests/core/strict_warning_bootstrap.php` as auto-prepend to
turn PHP warnings and deprecations into failures. The database-specific scripts
are run by the Python wrapper, not directly against site credentials.

## Behaviour

Webinars without a saved registration limit default to 170. Pending email
confirmations reserve capacity for their existing 24-hour lifetime; confirmed
current-consent registrations are shown in management totals. Lowering a limit
never removes existing registrations. Event row locks serialise bookings and
editor changes; confirmation follows the same lock order.

`action=dashboard` is administrator-only. It shows the selected webinar's cap,
counts, reminder controls, delivery aggregates and the mail worker heartbeat.
Morning sends use 08:00 SAST by default; the pre-event lead defaults to 90 minutes.
The existing Monday attendee reminder remains separately controllable. General
webinar-day announcements default off globally and may be enabled per event or
through `WEBINAR_DAY_ANNOUNCEMENTS_ENABLED` and its activation timestamp.
LearnTheBirds explicitly requested both morning emails for registered subscribers.
The existing weekly newsletter configuration remains independent.

Viewing or saving the dashboard never queues mail. The existing CLI scheduler
queues due work; the bounded Communications worker performs delivery. Enabling
an overdue send does not catch up historical announcements. Sent means transport
acceptance, not inbox delivery. Disabled controls and consent/event changes are
rechecked immediately before delivery. Already accepted messages cannot be recalled.
