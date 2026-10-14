# Context Instructor retirement — 14 October 2026

The old Context Instructor module is superseded by the shared User Details author biography renderer and Context Admin instructor/ownership workflow. Repository searches found no external PHP callers or module dependencies. Read-only registration checks confirmed it unregistered on local, chisimba.com, KengaLearn, LearnTheBirds, KengaPub, KengaSolutions and Heritage.

Remove its duplicate contact-card/ExtJS selector, controller, repository and installation schema from source. Existing tbl_contextinstructor records and tables are not altered or dropped; the old main-instructor selection is not automatically mapped to course ownership. The current canonical course authors and biographies are unchanged. Git history retains the old module if historical inspection is needed.

Deployment is pending. Recheck registration and any site-specific/stored module references before pruning deployed files. Use the existing instructor/ownership manager for changes, and the biography editor for author profiles. Do not reactivate the retired GET-based instructor-change endpoint.
