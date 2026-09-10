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

## Links, attached notes and page banner — follow-up

The top roll-up now hides the surrounding site banner and navigation, retaining all map tools, search, save and the restore arrow. Toolbar groups wrap when space is tight. The right-panel toggle remains independent. The fullscreen exit control is hidden until fullscreen is active.

Add link node creates a child reference beneath the selected node, selects it, restores the details panel when necessary and focuses Link. Enter a title and URL, apply the details and save. A link indicator opens the destination in a new tab and supports Enter from keyboard focus.

Add or edit note focuses the selected node's Note field, reopening the details panel when necessary. This uses the existing description storage, preserving older content and avoiding a database migration. Notes update as typed and are included by Save without a separate Apply step. Non-empty notes show a clickable note indicator. Notes are plain text with line breaks, visible under the map's existing viewing permissions; they are also available in the read-only embed's selection details.

Local Chrome verification on the retained disposable fixture confirmed banner/navigation hidden while toolbar actions remain visible, link creation with URL focus, restoring the hidden details panel for notes, multiline note and URL persistence after reload, visible indicators, and keyboard link opening. HTML-looking note content rendered as plain text. Graph round-trip and editor regressions pass. Module version0.4, JS21, CSS17. New labels registered through local Module Catalogue. These follow-up changes have not been deployed to KengaLearn.

## SVG icons and branch copy/paste — follow-up

The live personal folder contains grass1.svg, grass2.svg and grass3.svg. The original raster-only filter excluded these files, explaining the empty picker. Supported image formats now include SVG. Custom SVG content is delivered through File Manager as an img source, never injected as inline SVG or an object. Existing ownership and visibility checks remain. Empty pickers explain the file-format and save/reload workflow.

Local File Manager upload of km-svg-qa.svg, selection and save/reload passed; the saved image loaded at its expected32px width. No live files or map edits were made during diagnosis; the user's live map showed unsaved changes and was left untouched.

Copy node and descendants / Paste beneath selected node toolbar controls and Ctrl/Command+C/V duplicate the selected subtree within the currently open map. The clipboard is an immutable in-memory snapshot and clears when the page reloads; it is not a cross-map transfer feature. Text input copying/pasting remains native. Repeated pastes remap every node and relationship ID, preserve internal relationships, outgoing external links, notes, styles, icons and media intentions, expand the destination and preserve sibling order. Layout offsets are cleared for automatic positioning. Copying a root into itself remains acyclic because all copied IDs are fresh.

Local Chrome toolbar copy/paste, native keyboard copy/paste and persistence verified on the disposable fixture. Editor tests additionally check immutable snapshots, source independence, unique repeated IDs and preservation of links/notes/icons. JS asset22, CSS17, module0.4. Labels registered locally. These changes remain undeployed alongside the link/note/banner follow-up.

## Map library cards — follow-up

The library's title, scope control and create/import disclosures now share a dashboard-panel surface. Both disclosures start closed. Each available map and the empty state use the same existing skin panel primitive with module-specific inner spacing. This replaces the unstyled generic chisimba-card class on the library, without changing skin styles globally. CSS18 and module0.5.

Local Chrome verified initial collapsed disclosures, opening each form, white surfaces with borders, and the visual separation between controls and the available-map library. PHP template lint and whitespace checks pass. No map data changes; this layout follow-up is not deployed.

## Visible ordering and chapter-side controls

Move earlier/later are now in the main toolbar, available when the details panel is hidden. They reorder the selected node among its siblings; main chapters reorder among chapters on the same side. Boundary actions are disabled. Move chapter left/right moves the complete top-level chapter containing the selection, preserving parent relationships and descendant order. Root movement and already-current sides are disabled. The details-side selector now applies immediately. Save persists each operation.

Local Chrome fixture ee500d74958214460d0d6ceb1589d0fb reproduced Quiz followed by Love grasses under Chapter5. With the inspector hidden, Move earlier reversed their order and Move chapter left moved the complete branch. Save/reload preserved both. Move chapter right, save/reload again confirmed right placement and Love grasses before Quiz. Editor regressions and module contracts pass; source is local only. JS23; included in pending module0.5 with library layout. No automatic repacking or bottom branches introduced.

## Measured spacing and branch stretching — 10 September follow-up

Visible nodes are measured using their unscaled rendered width and height. Layout reserves each complete subtree's height, a32px gap and room for legacy vertical offsets; it retains containment order and chosen sides. Horizontal distances respect actual box widths. The canvas bounds include complete boxes, not only centres. ResizeObserver remeasures font/title-size changes; positioning updates retain existing focused and draggable elements. Subtree spans are cached per layout pass. Fit map uses the full bounds and supports zoom down to5% for large maps.

Stretch selected branch outward adds80px between the selection and its parent, moving its descendants together. Reduce spacing reverses this; it cannot reduce the measured collision clearance. The root cannot stretch. Spacing persists as bounded presentation.branchGap (0–2000px), using existing JSON storage without a schema migration. Tidy selected branch removes legacy manual offsets from that subtree, preserving sibling order, side and deliberate branchGap. Select the root to tidy the entire map. These are toolbar actions; no new drag handle or bottom/radial layout is included.

Local Chrome fixture ee500d74958214460d0d6ceb1589d0fb used a580px tall28px-font multiline label and a184px sibling. No box overlaps after render or direct title editing. Two stretches persisted160px after save/reload with sibling IDs in the same order; reduction and tidy also worked. The44-node fixture8349b4eca76d4ea2bd112932cd2c078e had zero pairwise box intersections and all boxes fitted inside the viewport. PHP graph tests cover spacing round-trip and bounds; editor tests cover tall siblings, legacy offsets, canvas bounds and stretching/tidying without changing relationships. Existing graph/editor/icon/contracts passed.

Module0.6, JS24, CSS18. New labels registered locally. Production grasses data untouched; deployment pending.
