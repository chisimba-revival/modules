# Contact form

Public route: `index.php?module=contactform`. Site administrator inbox: `action=manage`.

Install through the normal module catalogue. Set CONTACT_RATE_KEY privately to a random string of at least 32 characters. CONTACT_MODE defaults to disabled; preview captures without email; live requires fixed CONTACT_TO and CONTACT_FROM plus the shared mail module configuration. Do not put secrets into register.conf.

The existing shared plainmailservice owns delivery (SendGrid or the bundled
PHPMailer transport); it is not a module-specific mail implementation. Mock tests
do not prove delivery. The deployed helper, its vendor provenance and the shared
draft helper were reconciled into this checkout because they were missing here.
Configure CONTACT_TRUSTED_PROXIES only for verified proxy addresses. Arbitrary
forwarding headers are ignored.

Messages are stored privately before mail submission. Uncertain or interrupted delivery must be inspected manually; automatic retries never resend. A repeated form identifier with identical content returns the existing result. Changed content under that identifier is rejected. Only administrators can read the inbox. There is no historic WordPress import.

Drafts use the shared htmlelements draftform.js helper and sessionStorage scoped to the browser tab. Closing the tab may remove the local draft. CSRF tokens are refreshed separately and never restored from storage.

## Inbox and abuse protection

Version 1.001 adds private Inbox, Spam and Trash folders, compact message cards,
icon actions, selection of up to twenty messages, restore and exact-sender blocks.
All moderation requires administrator access, POST and a consumed CSRF token.
There is no permanent deletion. Moving, restoring and reviewing never send mail.
Blocking stores a keyed hash of the exact email address, not a domain-wide ban.

New submissions use signed form evidence, a honeypot, minimum form age and atomic
IP, sender and site budgets from the shared abuseprotection module. Defaults are
10/hour and 50/day per IP, 3/hour per sender and 100/hour and 300/day per site.
The original five-per-IP fixed-hour persistence limit also remains as a final
backstop. Adjust site configuration for expected legitimate traffic.

Shared SubmissionContentPolicy provides conservative review hints for generated
mixed-case names, multiple links and requests for security credentials. Registration
uses the same name rule. These hints are not proof of spam: suspicious contacts are
saved to Spam without email. Review Spam regularly. Check this page for spam scans
twenty historic inbox messages; it skips human-restored messages. Blocks apply to
future submissions. Existing mail cannot be recalled. No message bodies are sent
to an external classifier. Limits and exact-sender blocks cannot stop all bots.

Install through Module Catalogue before serving the new code: two new moderation
tables are required. The post-install hook verifies transactional storage. Deploy
the shared submissioncontentpolicy class and existing abuse budget/trusted-address
support together. Do not overwrite newer deployed framework/security files.

Tests:

- `php contactform/tests/service_test.php`
- `php contactform/tests/guard_test.php`
- `php ../framework/app/core_modules/abuseprotection/tests/submission_content_test.php`
- `php ../framework/app/core_modules/abuseprotection/tests/submission_budget_test.php`
- Local-only Catalogue and database checks: `tests/install_local.php` and
  `tests/runtime_test.php`, with CONTACT_TEST_SITE=/var/www/html/ch in the local container.

The original contact source was retrieved read-only from KengaPub on 2026-10-07;
no real inbox messages or credentials are included in source or test fixtures.
