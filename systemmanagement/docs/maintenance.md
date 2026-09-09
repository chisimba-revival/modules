# Plan maintenance and update the site

Use System Maintenance to announce an interruption, control public access and return the site to service. Saving dates or sending an email never changes site availability automatically.

1. Prepare first. Agree the exact changes, test them locally, and give the person deploying a start and end time in the site time zone. Keep an administrator session open.

2. Enter an Offline message and, for planned work, a Planned start and Planned end. Select Save plan. Check the saved times before sending your announcement.

3. Choose the email audience, enter a subject and message, and select Queue email notice once. Read the result and check Maintenance email delivery. A queued message is waiting for the worker; sent means the provider accepted it, not proof that it reached an inbox.

4. At the agreed start, select Take site offline. Confirm the status says Offline for non-administrators. Administrators can still work. The saved start time will not press this button for you.

5. Have the operator take and verify backups, deploy only the agreed changes, apply the necessary module updates and check the changed features while the site remains offline. The Deployment runbook below gives the technical procedure.

6. When the checks pass, select Bring site online. Check the public home page and a course page without an administrator session. Record what changed, the result and the backup location.

## Emergency maintenance

For urgent work, you can take the site offline immediately and send an announcement without planned dates. Save the offline message with both date fields empty to clear an old plan. When either saved date is absent the email says Unplanned maintenance; a complete saved pair is quoted instead. Sending the announcement does not take the site offline or reopen it.

## If an email submission fails

The audience, subject and message remain on the page after a handled submission error. No email queued means that submission queued nothing. A partial or uncertain result may have queued some messages: inspect the delivery history before trying again. The draft also remains after success for reference; pressing Queue email notice again creates a new send. This is not a permanent saved draft, so copy it before navigating away.

## Sender name and delivery

New emails use the configured site name by default. A deliberate custom sender name in Communications takes precedence. The verified sender email address is configured separately. Changing the name does not change mail already queued or sent. The application can confirm queueing and provider acceptance, but does not establish inbox delivery or that a person read the message.

## If the update cannot be completed

Keep public access off while the operator diagnoses or restores the previous release. Do not repeatedly retry a failing deployment. If the announced end time is approaching, arrange a revised window and communicate the change. Reopen only after the necessary checks pass.

## Working with a coding agent

Give the agent the target site, the agreed changes and the maintenance window. Tell it who will take the site offline and who will reopen it. It should report preparation, deployment and verification separately. A timer is a reminder to recheck the window and site state; it is not permission to deploy a second time.

## Worked example

On 9 September 2026, KengaLearn announced 13:30–13:45 South African time. The operator preserved the production release, deployed the Knowledge Map and maintenance-email fixes plus the membership layout correction, checked existing-map checksums, and reopened at about 13:34. The disposable test map was removed. Use this as a sequence, not as a reusable date or release identifier.
