# Registration Service

Public registration, email verification and account recovery use the canonical
identity, communications and audit services. Pending requests are separate from
canonical user accounts.

## Administration and help

Open **My Administration → Registration protection → Help with this page** for
review, dismissal, cleanup policy and recovery instructions. Administrators can
preview retention actions and open Registration Service settings from that page.
Names are review hints only; verified and linked accounts are protected.

## Background worker installation

See [Registration protection and retention](scripts/README.md) for service policy,
commands and tests. The companion `dev-environment/docs/workers.md` is the complete
installer checklist for all Chisimba workers; its
`registration-cleanup-worker-production.md` provides the full cron/systemd setup.
The companion `chisimba-info/user-guides/Registration_Protection_Administrator_Guide.md`
provides the administrator walkthrough.

Installing this module does not create a host schedule. Preview first, install a
daily cleanup schedule, then enable `REGISTRATION_AUTO_CLEANUP`. Communications
must be scheduled separately to deliver verification and recovery emails. No AI
worker or external bot service is needed for registration protection.
