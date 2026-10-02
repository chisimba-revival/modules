# Notes

Notes are canonical scoped resources that may be connected to many Chisimba
objects without copying their content. The initial integration vocabulary is
`kanban_board`, `kanban_task`, `kanban_subtask`, `context`, `content_page` and
`page`.

Access is deliberately independent of attachment. Seeing an attached Kanban
task does not disclose a private note. Direct user grants support `view`, `edit`
and `manage`; `context_role` and `group` principals are reserved for later
groupwork integration.

The preserved PHP 5 implementation is in `pagenotes_DEPRECATED`. Its registration
file is disabled, so it cannot conflict with this module. It remains available
as a migration source until the replacement has been accepted.
