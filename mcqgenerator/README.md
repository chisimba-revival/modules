# Legacy Question Generator links

This hidden module redirects old read-only `mcqgenerator` URLs to
`questiongenerator`. Saved tables are retained unchanged; the new module takes
catalogue ownership during its normal post-install hook. Do not uninstall the
old data-owning version to perform the rename.

POST requests and former mutation actions are redirected to a safe review page;
they are never replayed. Keep this module while bookmarks still use its route.
See [Question Generator](../questiongenerator/README.md) for migration and usage.
