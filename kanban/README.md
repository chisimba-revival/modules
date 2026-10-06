# Kanban workspace

0.144 consolidates scope, view actions, archived filter and the collapsed Create
board control into one responsive desktop toolbar, below the title/Help row.
Create board opens its full-width form only on demand and retains unsaved text.
The compact-field primitive must never constrain the whole toolbar form.
Tests now follow production CSS order (module before shared skin/canvas), include
real icons/Help and the creation workflow, and inspect complete header geometry.

In 0.143, Help shares the heading row and the three view actions stay together
beside or below the scope selector. Focus on boards uses the search icon, distinct
from fullscreen. Browser checks include actual shared icons/Help at 360px width.

**Focus on boards** (0.141) hides the surrounding page and fills the viewport
with all boards in the selected scope, keeping their current collapse states.
Use **Exit boards focus** or **Escape** to restore the page and keyboard focus.
This preserves drafts and does not refresh, save data, or change permissions.
It is separate from **Collapse**, which hides a board or task's contents, and
**Focus project**, which shows just one project.

In 0.142, **Focus on boards** sits next to **Full screen** with matching icon
buttons. In project focus, **Collapse all tasks / Expand all tasks** replaces
the unavailable board-collapse control. It affects only that project's task
details, never deletes or saves content, and retains editor drafts. Fullscreen
uses the shared surface background; rejected browser requests give feedback.
The shared skin's hidden-state and fullscreen rules must deploy with this version.

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

Saving an existing task updates its displayed title, description and notes in
place. It leaves the editor, project, notes disclosure and focus view open.
Failed or uncertain saves keep the text for retry and never refresh the page.

In 0.140, expanded task editors use the whole card width, with movement and
delete controls underneath. Subtasks have inline **Edit**, **Save** and **Cancel**
controls for editors/managers. Saving changes only the subtask title, without
refreshing the page or changing its completion. Each editor has its own tab-local
recovery draft. Concurrent title changes are rejected rather than overwritten;
keep your text and review the current title in another tab before retrying.

This change also requires the shared skin's `chisimba-action-disclosure` and
flow-notice spacing rules. Update the module's language/Help registrations as
part of installation. There are no database schema changes.
