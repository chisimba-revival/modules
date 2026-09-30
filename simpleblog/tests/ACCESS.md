# Membership-controlled posts

Existing posts have a nullable `required_tier_code`: null or empty is public.
No body, title, date, author or previous visibility is rewritten by the upgrade.
Authors choose Public or an enabled tier. An existing disabled requirement can
be retained, but cannot be selected for a different post. Omitted access in a
service call preserves the saved requirement. Access participates in the edit
conflict hash.

Membership Service owns `MEMBERSHIP_TIERS` in System Configuration. It is a JSON
array with stable `code`, integer `rank`, boolean `enabled`, `label`, and an
optional boolean `baseline`. One baseline supplies the membership of accounts
without active grants. Higher ranks include lower ranks. The seeded catalogue
preserves existing codes; Tier 2 is disabled for new choices. Disabled tiers
remain meaningful to existing grants and protected posts. Labels can be literal
site data or `mod_` language codes. Keep codes stable once used by any consumer.
Changing ranks changes access across consumers and should be deliberate.

Tier availability and registration policy are currently independent. Disable the
baseline entry in `MEMBERSHIP_TIERS` to hide it from new authoring choices; this
does not remove existing baseline access or disable account registration. The
legacy self-registration switch does not automatically hide that choice.

SimpleBlog asks Membership Service to resolve active membership from the existing
Entitlement Service ledger. Logged-in readers without the required membership
cannot discover those posts. Editors can still use the existing authorised saved
preview. Course restrictions and draft restrictions continue to apply.

`SIMPLEBLOG_RESTRICTED_PREVIEW_PERCENT` defaults to 30. Anonymous public-scope
readers see a target percentage rounded to a complete paragraph/block. The final
block is always withheld. A single indivisible block produces no body preview.
Zero disables the body preview. HTML is sanitised/rendered by existing services
before projection. Feeds, cards and Open Graph descriptions use the same projection.
Restricted body text is excluded from anonymous search matching.

The joining action resolves an enabled monthly membership product through
Payment Service, carrying its product code through Registration Service. If no
monthly product exists it opens the existing membership options page.
`SIMPLEBLOG_RESTRICTED_CTA_URL` overrides the destination with a site-relative or
HTTP(S) URL. CTA wording is managed through `mod_simpleblog26_join` and
`mod_simpleblog26_restricted_notice` in the language system. No payment is initiated
by viewing a preview. Media retain their existing File Manager permissions;
this feature protects article content, not independently public media URLs.

Upgrade via Module Catalogue, including dependencies and language refresh.
For the explicit local CLI path, run `php packages/simpleblog/scripts/upgrade-access.php`
with the application directory argument. It adds only the nullable column and
reloads the three module registrations; it is repeatable.

Focused checks: `tests/publishing_test.php`, `tests/access_preview_test.php`,
Membership Service `tests/tier_catalogue_test.php` and its contract test, Payment
Service `tests/payment_catalogue_contract_test.php`.

Local verification (PHP 8.5 bind-mounted environment): focused tests and module
contract tests passed; the upgrade was repeated successfully. Chrome verified
Public defaults, enabled choices (Tier 2 absent), restricted save/reopen, saved
editor preview, denied reader access, switching back to Public, and contextual
Help keyboard dismissal. Anonymous HTTP checks covered article/AJAX, listing,
RSS, search, the monthly registration return route, and an existing public post.
Anonymous in-app browser testing was blocked by the local certificate; responsive
viewport emulation did not take effect, so mobile rendering is not certified.
The disposable browser-test post was removed. Both pre-existing local posts
retained public access.

Registration policy is separate. The legacy `KEWL_ALLOW_SELFREGISTER` setting is
used by the registration block, but the current Registration Service does not
enforce it in its controller/service. Paid-only registration therefore needs a
separate central policy review; no such rule was added to SimpleBlog.

Course access-policy configuration still has its pre-existing fixed vocabulary.
This change makes membership inheritance, payment choices and SimpleBlog choices
data-driven; arbitrary new tier codes in course policy authoring need a separate
migration of that existing consumer.
