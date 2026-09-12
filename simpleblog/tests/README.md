# SimpleBlog Chisimba 26 publishing

## Implemented

Explicit site, personal and course scopes; native editor/media boundary; draft/save/preview/publish/unpublish; search, tags, monthly archive links, RSS, recent and scoped blocks; original author/date preservation; reusable userdetails biography cards; skin form/card/button primitives and icon service; language/systext and contextual Help. Canvas page design and legacy social/comment integrations are removed from executable paths. Existing post IDs and scope data remain. Historical description/canvas tables remain on disk for a separately reviewed migration, not used to drive presentation.

Canonical policy permits admins and site Lecturers (the underlying author role) personal publishing; explicit personal_publish grants support authorised assistants. Site publishing separately requires site_publish or site_manage. Course publishing requires the target course's author/admin role. Site managers/admins and course admins can edit the corresponding scope; ordinary publishers edit their own posts. Public queries positively require posted status. Course reading requires membership/author/admin access even for posted articles; private course titles and counts are not exposed through public listing/feed routes. Legacy SimpleBloggers membership is not automatically promoted.

Mutations require login, POST and native CSRF. Save validates persisted ownership, explicit immutable scope and status. Rich text passes through the shared utilities richtextsanitizer for both save and read, including legacy content. It preserves basic formatting, safe links and images/alt text; executable markup, arbitrary styles and embedded frames are excluded. Native editor implementation remains behind htmlelements/htmlarea, including file/media integration. No vendor editor is bundled in SimpleBlog.

Save uses an InnoDB row lock and content-version check; stale editors retain their submitted text and must merge with the current saved post. Post dates and original authors are not reassigned by editors. An explicit confirmation disclosure precedes deletion. Unrecognised/legacy mutation actions are unavailable; legacy direct view links with id remain usable.

## Upgrade

Deploy the modules changes together with framework utilities/classes/richtextsanitizer_class_inc.php and the existing author-biography feature. Required core modules are declared in register.conf. Install/upgrade SimpleBlog normally through modulecatalogue to 0.067; the SQL update converts existing post storage to InnoDB. Back up existing content first and confirm storage engine after upgrade. New installations also use InnoDB. Refresh module registration/language terms.

After install/upgrade, run scripts/configure-permissions.php with the Chisimba application path. This idempotently defines personal_publish, site_publish and site_manage in the canonical chisimba/simpleblog permission area; it grants nothing automatically. Use canonical permission administration to grant rights to the intended administrative assistant/publisher groups. Do not map translated role labels or grant the student group. Admins and site authors' personal eligibility use the existing identity services.

Old settings for social comments/canvas/default inference are no longer registered. Existing database settings are not deleted. The public default is site news; explicit blogid links still select personal/course scope. Ten posts per page are used. Existing block identifiers remain but share the protected reader; old about/search blocks link to the publishing page rather than invoking obsolete editors. Tag cloud is an accessible tag list. Course blog block includes its authorised management link.

## Verification on 12 September 2026

- PHP publishing_test.php: anonymous/readonly/author/assistant/site-publisher policies; scope denial; draft/unknown-state privacy; preservation of original author/date; cross-owner saves/deletes; stale-save conflict.
- Framework utilities richtext_sanitizer_test.php: active markup and hostile URLs removed, safe formatting/images/alt preserved, idempotent output.
- PHP syntax across module and whitespace checks pass.
- Local native editor/browser: new site draft saved, previewed, reopened, published, returned to draft, and deleted through explicit confirmation. Final disposable test-post count zero.
- Anonymous HTTP: draft 404, published 200, returned-to-draft 404. Published post present in RSS, draft absent. RSS parsed as XML.
- Local Help quick guide/full drawer inspected; labels use configured instructor/class terminology.
- Desktop editor inspected; 390px viewport verified page and loaded editor fit with no horizontal overflow; viewport restored.
- Installation verified in local tbl_modules at 0.065; table InnoDB; canonical publishing rights defined. First installation surfaced an invalid legacy release-date format, corrected to ISO before successful registration.

No production changes. Before production, exercise the actual configured assistant/author groups through signed-in browser accounts, review representative imported rich text/media against the supported formatting policy, and verify existing course/site block placements. WordPress importer, redirects, comments, scheduled publication and a full revision-history UI are separate work; no claim those features were implemented here.

## Shared builder and cards (12 September 2026)

Version 0.067 uses the shared contentblocks composition service, editor and media picker. Text, image/text, hero, reverse hero, video and manual slider blocks keep stable identities. Palette drops insert at the indicated position; drag handles and move buttons reorder. One block is edited at a time; previews and hidden fields retain the other submitted blocks. Changing blocks does not save. Save draft or publish persists the complete composition. Removal has one-step Undo until the next block change. Original legacy HTML is retained when first converted.

Featured image and alternative text belong to the post, not its composition. Cards reuse course-card skin styles and reserve the same media area without an image. Dates use publication chronology, local calendar-day labels and accessible full timestamps. Hero buttons support text placement and four image corners. All labels use language terms; post/posts and blog/blogs have independent systext mappings.

Classification supplies scoped categories and suggested tags. The same registered blocks power the page sidebar and Turn editing on placements: latestblogs, bloghome, createblog, blogcategories, blogtags, blogarchive, blogfeed and blogshare. Outside the publication page, blocks default to site scope; configure(type, scope, exclude) supports trusted callers. No arbitrary request can override an unreadable scope.

Install classification before SimpleBlog, run core_modules/classification/scripts/configure-service.php, then simpleblog/scripts/configure-permissions.php. For legacy data run simpleblog/scripts/upgrade-composition.php after the normal catalogue upgrade. It is idempotent, retains original content and migrates legacy tags. New tables and columns use normal registered definitions; unique classification identities are completed explicitly by configure-service.php.

Public media roots remain future work; existing file permissions still apply. Clean Slate is not yet converted. Help documents the current authoring workflow. Production release checks and source identities are recorded in the deployment directory.
