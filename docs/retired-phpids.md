# PHPIDS retirement — 12 October 2026

PHPIDS 0.6.3.1 was a self-contained demonstration module. Its controller invoked
`ids::scan()` only for its own public home page, displaying a report. The source
trace found no framework security/bootstrap/login hook, other module caller or
registration dependency. Registration declares no tables or installation hooks.

Read-only engine/catalogue checks on local Chisimba and all six existing sites
(Chisimba.com, KengaLearn, Learn the Birds, KengaPub, KengaSolutions and Heritage
Treazures) returned `checkIfRegistered('phpids') === false` on 12 October 2026.
Files were present on local and the first three live sites, absent on the others.
No module was registered, activated or unregistered for this audit.

Remove the complete `phpids/` module, including its old HTML Purifier copy,
demonstration controller, vendor tests and generated documentation. Do not replace
it with a scanner shim or claim it was providing request protection. Canonical
security, authentication, authorisation, CSRF and rich-text sanitisation remain
owned by the existing framework services. Git history preserves the removed code.

Validation: `php tests/retired_phpids_test.php` verifies absence of the module,
remaining module callers and module-owned HTML Purifier libraries. Run framework
HTML cleaner/engine and retirement tests alongside it. Local browser smoke checks
cover the normal authenticated home and Site Pages routes after removal.

Deployment is pending. Remove `packages/phpids` from deployment releases only
after rechecking registration and deployed-source drift. Refuse removal on an
unexpected registered installation until its use has been investigated. There is
no database migration or data deletion in this change. Local's modules mount
reflects the removal directly; no production files were changed by this audit.
