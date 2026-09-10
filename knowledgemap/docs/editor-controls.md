# Knowledge Map editor controls — 10 September 2026

## Editing journey

Click the node's + control to add a child. Ctrl-click (or Command-click) the + control on a non-root node inserts a sibling immediately after that node. The main node always adds a child. With a node focused, Insert adds a child and Shift+Enter adds a sibling. Newly inserted nodes receive keyboard focus so an outline can be extended repeatedly.

Node colour in the inspector updates immediately. The palette toolbar button applies that colour to the selected node and its descendants. Font colour remains in the typography popover. Save persists the changes.

The top-control icon points down while top controls are hidden; the right-panel icon points left while the panel is hidden. Both revert when restored, synchronised with their accessible state.

## Personal icons

Open the icon picker and choose Manage personal icons. In File Manager's My Files root, create km-icons and upload PNG, JPEG, GIF, WebP or BMP images. Save the map before reloading to refresh the picker. The new Personal icons group reads only the current user's dedicated folder.

Icons persist as file:<file-id> references, independently of media intentions. File Manager retains responsibility for uploads, storage and delivery. Saved references are resolved for editor and read-only embed; foreign private/hidden files are omitted, as are non-raster files and files outside a personal km-icons folder. Public referenced icons can appear to other viewers. File removal or permission changes can make an icon unavailable; map text is unaffected.

## Implementation and validation

The former quick-action button extended across a gap beyond the node's hover area. The controls now overlap the node edge and remain reachable, including on an unselected root. Colour previously waited for the details form submission; its input/change events now update presentation without resetting pending inspector text. Insertion now keeps focus on the new node.

Local Chrome verification used disposable personal map 8349b4eca76d4ea2bd112932cd2c078e, titled QA editor controls 10 September. The test created ten nodes and verified repeated root additions, collapse/expand followed by insertion, Ctrl-click siblings, Shift+Enter siblings, repeated node/branch and font colour changes, and save/reload. A native drag moved a chapter from left to right, cleared drag decorations and persisted its side. Move later changed and persisted the chapter sequence. Both disclosure arrow graphics were inspected in their hidden state. A 32-pixel PNG was uploaded through File Manager into km-icons, selected on the map, and verified loaded after saving/reloading.

Graph, module contract, editor behaviour/save-state and personal-icon ownership/format/escaping regression tests pass, as do PHP and JavaScript syntax and whitespace checks. Read-only icon library handling is covered at service level; this turn did not run a separate authenticated embed-browser journey or a multi-user sharing journey.

Source baseline: modules a30034766; both modules and framework were clean main and equal to freshly fetched origin/main before editing. Existing work from the prior day was already committed. Module version is 0.3, JavaScript asset version 20 and CSS version 16. Load the new labels using Module Catalogue when assembling/staging the update. Labels were registered locally through Module Catalogue's Text Elements screen.

No production deployment or production data modification was performed. The grasses course was not used as a fixture. The local disposable map and its test icon are retained for review.
