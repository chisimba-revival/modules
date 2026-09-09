# Knowledge Map editor refinements

Built against modules commit 848e093c1, 9 September 2026. No production data migration is required.

## User journey

Select a node, open the icon picker, and use Media: Create video, Use video, Create text or Use text. The details panel also provides Media purpose. Create means new material is required; use means existing material is required. These are planning intentions, not automatic creation or consumption actions. Changing a decorative icon preserves the intention; choose No media purpose and Apply to remove it.

Select a chapter and use Move earlier / Move later to change its position among branches on the same side. Main branch side chooses left or right. Put all chapters on one side for a continuous top-to-bottom sequence. Add sibling inserts immediately after the selection. Dragging a main branch onto empty canvas sets its side and sequence position with automatic spacing. Dropping onto a different node reparents it; collapsed destinations expand. Save persists the order and side.

Existing sides are preserved at editor initialisation, including imported `dir` values. Changes do not alphabetise chapter titles or infer chapter numbering. Explicit ordering removes manual offsets from the sibling group to restore consistent spacing. A main-branch canvas drop clears the main branches' offsets. Reset selected position restores automatic spacing for a freely positioned descendant.

Push and pull are provider integration concepts: outward from the selected graph scope, or inward from another module. The registry recognises node, descendants and whole_map scopes and push/pull/both directions. The current buttons are disabled placeholders and the controller has no execution handler. File import is separate; no export feature is implied.

## Data contract

`node.presentation.mediaIntent`: allowlisted `create-video`, `use-video`, `create-text`, `use-text`. This stable identifier is separate from `presentation.icon` and is retained by graph normalisation, persistence and subgraph extraction. Future providers should inspect mediaIntent, not infer intent from icon names. Existing nodes without it remain unclassified. There is no automatic behaviour yet.

Containment relationship `order` stores compact integer sibling ranks. `presentation.side` persists the main branch side. Finite bounded offsets and fontFamily now survive normalisation. Timestamp ranks previously exceeded the portable signed 32-bit database range and fell back to source array indices.

## Movement corrections

Title blur commits text without destroying the drag source DOM. Drag state is cleared on drop, drag end, Escape and window blur. Pan state also clears when pointer capture is lost. Canvas drop compensates for the point where the handle was grabbed. Workspace resizing keeps the root viewport anchor when scroll bounds permit. Reparenting rejects cycles, root movement and no-op parent drops; it removes stale offsets and expands the target.

Save requests are serialised and edits made during a pending save retain the unsaved indicator.

## Verification and release status — 9 September 2026

The existing draft was preserved with Git stash, clean main was reconciled by a successful fetch and fast-forward-only pull, and the draft was restored. Both main and origin/main were 848e093c1 at reconciliation. The supplied patch was not reapplied over the later draft: the existing working copy already included additional icon-preservation and pending-save tests. Module version is now 0.2; the Media heading also uses the language registry. No stylesheet changes were needed.

Local PHP 8.5.4 graph tests, module contracts and all module PHP syntax checks pass. JavaScript syntax and behaviour regressions pass, including pending-save edits, fresh revision/token use, Escape cancellation, window blur and lost pointer capture.

Chrome at https://chisimba.test:8445/ch/ verified the following on a newly created personal disposable map, c74f554de753883dd8ff12021ffa3e6c:

- All four semantic media icons render and survive database save/reload, with mediaIntent stored separately from icon.
- Chapter order and compact relationship ranks survive reload; Move earlier followed by Add sibling inserts after the selected chapter.
- Direct title editing immediately followed by native drag succeeds. Main-branch drops to the left and right restore automatically spaced branches without dragging indicators remaining.
- Reparenting onto a collapsed branch expands it, showing its existing child and the moved node; the result survives save/reload.
- A 40-second database row lock on only the disposable map delayed a real browser save. Editing during the request retained Unsaved changes after completion. The next save used the new revision and retained the edited description after reload. The diagnostic lock rolled back without modifying data.
- Changing a decorative icon independently, then applying details, preserves the semantic purpose.
- Push/Pull remain disabled and explain their unimplemented module integration purpose.
- A temporary HTML fixture generated by the actual PHP embed renderer displays all four media icons and labels with no contenteditable, draggable or save controls. Collapse/expand remains available.
- A browser fixture with a synthetic dragstart followed by a real Escape key clears active drag styling/state. Native Escape during an operating-system drag is not independently verified: the browser tool exposes a complete drag operation, not separate mouse-down/move/up steps. Native successful drops and automated cancellation-handler tests are verified separately.

The local module catalogue successfully applied only knowledgemap 0.1 → 0.2, loading the language entries. Other pending module updates were untouched.

An unrelated existing PHP 8.5 failure in the sitepages HTML cleaner prevented saving a new draft embed page: HTMLSax3.php attempts to modify handler_default on null. The generated renderer fixture avoids changing that unrelated subsystem; end-to-end embedding through sitepages remains blocked by it. No existing page was edited.

Staging verification is pending. The current deployment notes do not identify an active staging server; an isolated local staging copy is being prepared. No production host, production database or grasses map has been touched. Production deployment is not authorised.
