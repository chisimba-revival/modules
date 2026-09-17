# Assessment short-name repair (1.473)

Earlier Gradebook code added `short_name` to the fresh-install table definition
without providing an upgrade for existing tables. Adding an Assessment Plan
item could consequently fail with `Unknown column 'short_name' in 'INSERT INTO'`.

Deploy Gradebook 1.473, then apply the Gradebook update in Module Catalogue's
Updates page before adding activities. The preinstall hook adds the missing
column using the fresh-install definition: a 16-character text field, not null,
with an empty default. Existing assessment items and weights are retained.
Existing short names are left untouched. New installations use the normal table
creation process. Inspection or alteration failures stop the update before its
version is recorded, allowing a retry after the database problem is resolved.

Verify by adding an existing activity to an Assessment Plan, setting its short
name on the Assessment Sheet, saving, and reloading the sheet.

Do not uninstall Gradebook to repair this error; use the module update.
