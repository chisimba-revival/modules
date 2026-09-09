# Deployment runbook for operators and coding agents

Follow this procedure for a bounded update to an existing site. Use current evidence and the authorised scope; never substitute a local development database for production data.

1. Establish the release boundary. Read the project Constitution and current deployment record. Record the target hostname, active release, framework and module identities, runtime, installed versions, agreed changes and saved maintenance window. Confirm who owns deployment and reopening; only one operator or task should execute it.

2. Reconcile the sources. Preserve unrelated local work, fetch the relevant remotes, inspect the differences against the deployed baseline, and commit and publish the approved changes. Run focused syntax, behaviour and browser checks using development data. Record meaningful limitations rather than claiming untested behaviour passed.

3. Build a reviewable candidate. For a full release, assemble complete clean Git snapshots. For a bounded release, copy the verified current production release into a new immutable directory and replace only the named modules or exact reviewed patches. Hash inputs and record the baseline plus every overlay. Do not deploy all pending main-branch changes accidentally.

4. Before switching, verify the current release and input hashes again. Check the actual time and saved window, and confirm maintenance is active. Save a database backup and persistent-file backups; verify that each archive can be read and record checksums. Capture before-state evidence for the affected existing data.

5. Test the candidate against the production runtime before it becomes current. Switch the release pointer atomically and restart only the required service. Wait for health checks, then use the canonical module catalogue to update only the affected modules and their required dependencies. Check installed versions and language entries.

6. Verify the changed journeys while maintenance stays on. Inspect existing data read-only and compare the before/after evidence. Use a disposable personal item for a save/reload check when needed, remove only that test item, and compare again. Test mail payloads with a fake queue; do not send real mail without explicit authority.

7. Reopen through System Maintenance only after checks pass. Confirm anonymous home, login and relevant course access, service health and recent errors. Keep verified backups and a usable previous release. Remove temporary helpers, stop any fallback timer and record source identities, times, checks, data comparisons and remaining limitations.

## Hard release gates

Stop before changing production if the target, active baseline, archive hash or agreed scope differs from the prepared release. Do not start outside the authorised window. If there is not enough time for backup, deployment, verification and rollback, report that and arrange another window. Existing authorisation remains valid for the agreed actions; do not invent additional approval steps.

## Maintenance is an application control

The public-offline switch blocks non-administrators through the application; it is not a database lock. For changes affected by background workers, uploads or external integrations, identify and pause the relevant writers during the critical step and resume them deliberately. Keep administrator access and a rollback route available.

## Protect the data

Keep configuration, secrets, uploads, user images, sessions and the production database outside code replacement. Do not import, reseed, rewrite maps or update courses just to deploy source. Checksum comparisons can prove unchanged tables only when no legitimate writes occur during the comparison; account for expected activity and distinguish it from deployment changes.

## Rollback

On failed candidate tests, do not switch. On a failed post-switch check, restore the recorded previous release pointer and restart the affected service while public maintenance remains on. Keep the failed release evidence and verified backup. Code rollback does not undo schema or data changes; plan reversible migrations in advance. Never automatically restore an entire database over legitimate intervening work.

## Catalogue updates

Use the module catalogue patch service or the normal protected administration forms. Avoid Apply all updates for a bounded release. Language text and menu names can need a catalogue update even when no schema changes. Inspect migration and default-data files before running the update; confirm the exact installed versions afterwards.

## KengaLearn reference paths

The server is reached as derek@104.248.35.30. Code is selected by /srv/kengalearn/app/current; releases live under /srv/kengalearn/releases and backups under /srv/kengalearn/backups. Shared configuration and files live under /srv/kengalearn/shared. Verify these paths against the current host before use. Read credentials in place and never print them or copy them into reports.

## Repository references

The workspace uses separate framework, modules, canvases and shellscripts repositories under /run/media/derek/main/chisimba-revival. Read shellscripts/kenga-learn-deployment/README.md and the latest release record. The 9 September example is release-preparation-20260909.md with deploy-task-fixes-20260909.sh and manage-task-fixes-20260909.php. Those scripts have fixed dates, hashes and release identities; prepare and review a fresh script instead of rerunning them unchanged.

## Reliable execution and reporting

Use strict shell error handling, bounded waits, explicit exit checks and a rollback trap. Keep secrets out of diagnostics; append operational evidence to workspace killme.txt and write a durable release record. A script reaching its last line is insufficient: verify the active release, module versions, data preservation and real page behaviour. Mark the record complete and disable scheduled follow-ups so they cannot redeploy.

## Release acceptance record

Verify configuration changes with a fresh process or database read: the existing configuration object can retain an old cached value after saving. Record exactly which changes are local only, published, deployed and verified. Include the source commits or module trees, archive and patch hashes, previous and new release, backup path, timestamps and time zone, tests, temporary-data cleanup and any unverified features. A human should be able to locate the rollback target and understand the result without reading a chat history.
