# Legacy publishing retirement

Run `php cms/tests/publishing_retirement_test.php` from the modules repository.
The test invokes both real controllers using a service-denying fixture controller.
CMS `serverpc` and SimpleGal `metaweblog` return 410 before module services load,
for GET/POST and case variants. Normal SimpleGal view dispatch preserves its data.
The four retired classes are listed in `retired-publishing-paths.json`; deployment
must delete those files, not just copy the changed controllers.

Derek authorised retirement on 12 October 2026. No content, gallery records,
schema or ordinary editing features are removed. Version bumps: CMS 1.272,
CMSAdmin 1.648, SimpleGal 0.006. These dormant modules are not registered on any
of the six managed production sites; no production install/enable was performed.

This test is not a complete modernisation/acceptance test of these legacy modules.
They still require review before activation. SimpleBlog is unrelated and unchanged.
