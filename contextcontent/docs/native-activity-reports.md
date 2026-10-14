# Native activity reports — 14 October 2026

Version 2.105 replaces both ExtJS reports with server-rendered native HTML. Existing report entry URLs remain. Filters use inclusive calendar dates, current student membership or all recorded users, and name/username/description search. Results are paged in groups of 30, with deterministic date/name ordering; the summary sorts by access count or name. Counts are content visits, not login sessions or learning duration.

The dbTable-backed report service checks the logged-in administrator/teaching role and active course on every call. Dates and sort choices are validated, values are bound, title lookups are course scoped, and rendered values are escaped. Current-student filtering intentionally uses current canonical group membership; choose all recorded users to include former members. Deleted content falls back to its recorded identifier.

CSV uses the same scoped query and filters, downloads through the authorised report action, escapes spreadsheet formula prefixes, and processes records in 500-row batches. No public CSV files are created. The old unused JSON/CSV methods, fixed-filename exports, ExtJS grid/search scripts and date-picker templates are removed. Previously generated CSV files on existing sites are not deleted by this release; deployment should locate and assess any such historical exports separately. No schema or historical activity data changes.

Local catalogue update passed. Regression checks cover invalid dates, parameter binding, course isolation, current-student filtering, bounded pagination, logged-out/student denial, administrator/lecturer access and formula-safe CSV cells. Existing contextcontent active-schema/rebuild contracts pass. Browser checks cover real four-row activity data, the matching one-user/four-access summary, CSV contents, alternate date ordering, search/empty results, Help and Escape. At 390 px fields fit and the table scrolls within its wrapper; the document does not overflow. No browser console errors or new PHP warnings were observed.

Production deployment is pending. ExtJS callers in other modules and jQuery remain separate work.
