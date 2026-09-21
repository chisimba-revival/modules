# Kanban workspace

Projects open collapsed so the overview stays compact. Select **Expand** to work
in a project. Each project heading carries the shared folder/project icon.

**Focus project** fills the viewport with the selected project, hiding the site
banner, menus and other projects. **Exit project focus** or **Escape** restores
the overview, its scroll position and the project's previous expanded state.
Focus changes no saved data and keeps the same forms and unsaved text in place.
Surrounding controls are excluded from keyboard navigation while focused.
Recovered drafts still open their project so unfinished work is visible.

The page's existing **Full screen** control remains available for the whole
workspace. Project focus itself does not require browser full-screen permission.

This release requires the shared skin's `chisimba-focus-surface--active` and
`chisimba-focus-open` primitives. Update Kanban through Module Catalogue to load
the labels and contextual Help. There are no new workers or database tables.

See [tests/README.md](tests/README.md) for existing regression checks. Check the
collapsed overview, project focus, Escape/focus return and narrow screens without
submitting changes to real boards.
