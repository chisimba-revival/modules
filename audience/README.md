# Audience subscribers and newsletters

Account-independent subscriber contacts. `users` refers to this audience, not login accounts.

Administrators enter from **Users** / **Newsletters** on the webinar catalogue, or `module=audience`. Contact search supports all four consent states. Contact edits are revision checked and audited; subscribing again or changing a subscribed email requires explicit consent evidence. Address changes clear mailbox verification. Suppressed and unsubscribed contacts never receive campaigns. No automatic conversion of imported consent.

Newsletter drafts support plain text, optional public upcoming-webinar details and optional shared support text. Save generates the preview; queuing freezes that reviewed text and snapshots subscribed contact IDs/revisions. One bounded CLI process fans out through Communications, with per-recipient transactions and idempotency keys. A fresh unsubscribe link is appended for each contact. Current consent/revision and campaign cancellation are checked again at transport time. `dispatched` means handed to the outbox, not delivered. Cancelling does not recall already sent email.

The installed Webinar adapter owns calendar selection and public event formatting; shared Audience owns contact consent and campaign dispatch. Automatic campaigns have deterministic period keys, a 24-hour dispatch window and event-time checks, so cancelled/rescheduled or stale events are not sent from old snapshots. No private joining URL is included. Registration confirmations and attendee reminders retain their separate Webinar policy.

## Automation

`WEBINAR_ANNOUNCEMENTS_ENABLED` defaults FALSE. Turning it on records `WEBINAR_ANNOUNCEMENTS_SINCE`; there is no historical catch-up. The **general upcoming-webinar newsletter runs every Monday**, whether or not a webinar takes place that week, and includes all bookable upcoming webinars. Default time: 08:00 in `WEBINAR_ANNOUNCEMENTS_TIMEZONE` (Africa/Johannesburg). Empty lists do not send. There are no first-of-month, after-event or general event-day announcements. All other general messages are manual.

**Confirmed, subscribed booked attendees** separately receive reminders on the Monday of their webinar at08:00, its morning at08:00, and90 minutes before it starts, in the webinar's own time zone. For a Monday webinar, the Monday/morning reminders are combined. Attendees booking after a reminder was due get subsequent reminders, not historical ones. The shared support paragraph is included in these reminders when nonempty. Joining links stay within booked-attendee mail. Disabling the weekly newsletter does not disable attendee reminders; all transport still requires the shared delivery worker to be activated.

Support text is editable in Email settings. Saving is incremental and errors retain the current form. Contextual Help documents consent, sending, scheduling and recovery; Escape closes it. No third-party mailing-list library is required.

## Deployment and delivery

Install Communications >=0.108, Audience >=0.004 and Webinar >=0.012 normally. The Audience hook ensures InnoDB and unique contact/campaign/outbox identities; duplicates cause failure, never deletion. The Communications delivery gate supports a module-specific `policy_class` constrained to `<policy_module>communicationpolicy`, avoiding PHP class-name collisions in a shared worker.

Run the existing canonical `core_modules/communications/scripts/run_outbox_worker.php` from ONE externally locked CLI worker. Do not run parallel workers. It schedules due webinars, announcements and bounded audience batches. Configure the transport and recipient restrictions separately; source deployment does not authorise production sends. No timer is installed by module updates.

## Verification

- `php webinar/tests/announcements_test.php`: date boundaries, grouping, time zones, activation, cancellations, drafts, year rollover.
- `audience/tests/campaign_integration_test.php`: real MariaDB disposable database (must be named `audience_test_*`). Set `AUDIENCE_TEST_DSN`, `AUDIENCE_TEST_USER`, `AUDIENCE_TEST_PASSWORD`, and `AUDIENCE_TEST_FRAMEWORK`. Tests permission denial, consent, conflicts, duplicate addresses, optional sections, frozen recipients, resumability, unsubscribe/resubscribe and cancellation. No transport is invoked.
- Existing webinar registration/editor tests and Communications gate tests.
- Browser: save/preview with and without optional sections, cancellation, subscriber search/editor, Help/Escape, anonymous denial and admin card links. Never queue a test campaign to the real audience.

Newsletter recording introduction (Audience 0.005 / Webinar 0.013): Monday
newsletters insert the latest completed public, noncancelled webinar's YouTube
recording after the greeting and before the message. No older recording is used
as a fallback when that webinar has none. Manual drafts have an editable greeting
and an opt-out checkbox, initially enabled for new drafts. Existing drafts retain
their saved preview until resaved. Content remains frozen for queued campaigns.

Audience 0.006 supports {FIRSTNAME} in newsletter text. Each recipient's queued
copy replaces it with the first part of their stored full name, or “there” for
missing/blank/email-like names. New greetings default to Hello {FIRSTNAME},.
The saved preview retains the placeholder; existing drafts are not rewritten.
