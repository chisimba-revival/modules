# Registration protection and retention

Update `abuseprotection` (0.2), `registration-service` (1.022), `myadmin`
(0.108) and the coordinated Chisimba Reborn skin. There are no schema changes.
Normal Module Catalogue updates register the new settings and language items.

The administrator dashboard links to **Registration protection**. The bin icon
beside a reminder opens a confirmation page; it dismisses only an unverified,
unlinked pending request. Existing accounts retain the canonical User
Administration review/archive workflow. Random-looking mixed-case names produce
review hints only, including in the latest completed-registration list.

Configure Registration Service through System Configuration. Defaults:

- New pending requests expire after 7 days. Existing deadlines are preserved.
- Expired/dismissed pending details are redacted after another 30 days.
- Submission budgets: 20/IP/hour, 100/IP/day, 3/email/hour,
  100/site/hour and 500/site/day. Registration and password-recovery budgets
  are separate. Successful requests count. Verification emails have separate
  3/email/hour, 10/email/day and site budgets, including resend and admin paths.
- `REGISTRATION_TRUSTED_PROXIES` is empty by default. Add only the actual trusted
  reverse proxy IP/CIDR. Forwarded addresses from other peers are ignored; trusted
  chains are read from right to left. Never trust an entire public network.
- `REGISTRATION_AUTO_CLEANUP=0` until a preview has been reviewed and a schedule installed.

From the installed application root:

```
php packages/registration-service/scripts/run_registration_cleanup.php --preview
php packages/registration-service/scripts/run_registration_cleanup.php --apply
```

Preview is the default and prints counts without personal data. Apply also
requires `REGISTRATION_AUTO_CLEANUP=1`. Install a daily server cron/timer using
`flock` so runs cannot overlap. Run under the same application installation and
configuration as the web process. Each run handles up to 200 candidates; check
backlog counts and increase frequency if traffic exceeds that. Failed runs
return nonzero. A partial batch is safe to retry. Never run this through a
public HTTP route. The application script itself denies non-CLI access.

Expiry and dismissal clear the saved password and invalidate verification by
state/expiry. The verifier locks the pending row before changing state, as do
cleanup and canonical provisioning. Cleanup preserves verified/provisioned rows,
linked user IDs, selected payment products, and canonical username/email matches.
It never deletes a user, enrolment, payment, or learning record. Redaction removes
pending names, username, contact and identity-document fields. Opaque identifiers,
timestamps and minimal account-event audit records remain. Email outbox and legal
acceptance records follow their owning service's retention rules; already queued
mail may still arrive, but a dismissed request's verification link cannot activate it.

`AbuseProtectionService::admit` uses an installation-key HMAC per dimension and
MariaDB advisory locks to serialise check-and-record across workers, with the
existing registered abuse-event table. Raw IP/email values are not stored there.
Login failure throttling remains unchanged. Limits are bot friction, not a claim
to identify every automated or manually operated spam account.

## Tests

Run the framework abuse-protection tests and the existing registration/myadmin
contracts. `tests/registration_cleanup_integration_test.php` requires
`REGISTRATION_GUARD_TEST=1` and the exact disposable database name
`registration_guard_test`; it refuses other databases. It creates synthetic
records and removes those fixtures in `finally`. It expects the module update,
a canonical administrator with ID 1, and automatic cleanup enabled in that
isolated database. It does not send email or modify existing user records.

## Installer and administrator documentation

In the companion repository checkouts, start with `dev-environment/docs/workers.md`
for the complete inventory and installation checklist. The detailed recipe is
`dev-environment/docs/registration-cleanup-worker-production.md`, including cron
and systemd alternatives, enabling the policy, expected output, logs and stopping.
Registration cleanup is not installed by the Communications or AI worker installers.

The administrator walkthrough is
`chisimba-info/user-guides/Registration_Protection_Administrator_Guide.md`.
In the application, open **My Administration → Registration protection → Help with
this page** for contextual instructions on review, dismissal, retention and recovery.
