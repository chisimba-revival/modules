# System Maintenance help and sender branding — 9 September 2026

System Management is renamed System Maintenance in its registered name, administration link, heading and access message. The internal module identifier remains systemmanagement so existing routes, permissions and data remain valid.

The console uses the canonical Help service for an administrator guide, with a separate full-page deployment runbook for operators and agents. Both topics are administrator-only. Their text lives in register.conf; matching Markdown copies are maintenance.md and deployment-runbook.md. A regression check keeps the copies in agreement. System Maintenance now depends on Help and is version 0.5.

Communications 0.106 resolves its empty or legacy Chisimba sender display name from the site configuration for each new queued message. Deliberate custom names remain supported and the verified sender email address is unchanged. Existing queued or sent mail is not rewritten.

Local verification: both catalogue updates applied; PHP 8.5 behaviour/contract/syntax checks passed, including sender branding, custom names, changing site names, help authorisation and retained email drafts. Browser checked the renamed breadcrumb/heading, expanded help, readable guide drawer, Escape dismissal with focus return and full-page operator runbook. No email was sent.

Production configuration correction: KengaLearn COMMUNICATION_FROM_NAME changed from Chisimba to KengaLearn using the canonical configuration service. A fresh database read verified persistence; a fresh invocation of the actual communication-service normalisation method verified sender_name=KengaLearn without inserting an outbox item. Configuration objects cache previously read values within a request, so same-object readback is insufficient. No site availability change or code deployment was performed for this follow-up.

Release state: code changes are ready for the next authorised deployment. Apply only the affected module catalogue updates (System Maintenance 0.5 and Communications 0.106), including the existing Help dependency if required. No data migration is introduced. The previous maintenance window does not authorise another interruption after it ends.

Version correction: Communications 0.106 supersedes the initial 0.1.5 sender-name release because the current catalogue compares decimal numbers. Regression tests now exercise upgrade discovery from every dotted legacy version.
