# Workgroup file sharing

Modernised 17 October 2026, Workgroup 1.196 and Workgroup Admin 1.222.
Install/update through Module Catalogue. No schema changes are required.
Use with framework File Manager 1.114 or later (private-download termination fix).

The supported journey is scoped file sharing and membership administration.
The obsolete landing page integrations with the missing old Forum and other legacy
activities are no longer loaded. Existing tables/IDs and file metadata are retained.

A current course member must also belong to the group. Current teaching staff and
site administrators can manage groups in the selected context. Membership edits
use the same boundary as file access. Every write requires POST, shared CSRF,
current persisted permission checks and a checked dbTable savepoint transaction.
Revision snapshots reject stale changes; create IDs make identical retries safe.

Sharing links an existing File Manager file; it never grants new file permissions.
Ordinary users may link their own readable files; managers may link other readable
files. Recipients must independently satisfy the original file read policy.
Private files therefore remain private unless their existing permissions allow the
recipient. Removal deletes the association, not the original file. The legacy
`modifierid` is retained as the association owner, including when staff edit it.
Owners/managers can remove stale links whose originals are unavailable.

Files and membership use native forms, shared skin/icons/Help and the modern File
Manager picker. Workgroup no longer loads jQuery or the old AJAX file-list script.
The picker opens in a normal tab and returns selection to the draft. Text is escaped;
descriptions are plain text. Saves preserve drafts on validation/token/conflict errors.

Local and isolated validation is recorded in tests/README.md. Workgroup Admin was
fresh-installed and updated in the disposable clone; it remains dormant on the
real local installation. Production deployment is a separate step.
