# Public pages

Administrators can create, edit, publish and archive ordinary site pages using
the shared content composition editor. Public view routes never expose drafts to
anonymous visitors or ordinary members.

## Delegated page editors

After installing/updating the module, run:

```sh
php packages/sitepages/scripts/configure-permissions.php /path/to/chisimba/app
```

This defines the canonical `chisimba` application, `sitepages` area, `manage`
right. It grants nobody access. Assign this right only to a reviewed editor group
through Chisimba permission administration. Editors can manage all site pages,
including drafts, and use contextual Help. They receive no administrator rights
and no access to private contact enquiries. Article editing is a separate
`simpleblog.site_manage` grant. Removal of the grant or membership takes effect
through the canonical permission service; missing definitions fail closed.

All save/archive operations retain login, permission, POST and CSRF checks. Saves
use the shared rich-text/composition validation and existing version checks.
