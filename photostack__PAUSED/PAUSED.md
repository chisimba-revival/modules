# photostack paused — 17 October 2026

Preserved at Derek's explicit request following read-only checks of local Chisimba
and all six production sites. The module was unregistered everywhere checked,
with no corresponding tables or targeted stored route/block references.
No original source or database data was deleted. The original module path is
intentionally absent; register.conf is preserved as register.conf.paused so
catalogue discovery excludes the preserved directory.
Do not rename/register it to bypass a revival review.

Local-album photo gallery, not a Flickr integration. Review album ownership, edit/delete CSRF and file access; remove the external HTTP jQuery loader before revival.

Before revival: enforce current persisted ownership and scope on reads and writes;
require POST and CSRF for mutations; use shared dbTable services and checked
transactions/revisions; escape output; replace legacy jQuery with shared native
controls; fix PHP 8 warnings; run isolated installation, security, database and
browser tests. Archived code is not certified safe for activation.
