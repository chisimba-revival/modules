# Working on Chisimba 26 — application modules

Read this repository-wide guide before changing a module. It is for human
contributors and coding assistants. More specific `AGENTS.md` instructions apply
to their subtree. The user's explicit task instructions take precedence.

## Start with the actual installation

Chisimba is a modular PHP framework supporting learning and other applications.
This repository contains application modules; framework base classes, core modules
and the maintained skin live in the separate `chisimba-revival/framework` repository.
A runtime commonly exposes this repository as `packages/`, but inspect its real
configuration. Do not assume a particular host path, container name or site URL.

Check the branch/upstream, local changes, relevant module version, PHP runtime,
container mounts and overlays before editing. Source on another computer or branch
may differ. Preserve existing databases, files and uncommitted work. Never blindly
reset or rebuild an installation to obtain a newer module.

The codebase includes historical modules and modernised modules. Old code is not
a design or security specification. Find the active caller, shared service and
current tests before following an example. Dated reports do not establish current
production state. If present, read the broader project's
`../chisimba-info/chisimba-constitution.md` and the framework's `AGENTS.md`.
This guide stands on its own when those sibling checkouts are unavailable.

## Module structure and contracts

Typical structure (inspect the target module for variations):

- `controller.php`: module controller, request/action dispatch and authorisation.
- `classes/*_class_inc.php`: services, models, repositories and reusable blocks.
- `templates/content/`, `templates/layout/`, `templates/page/`: presentation.
- `resources/`: module-owned progressive enhancement and assets.
- `register.conf`: identity, version, dependencies, language, tables and navigation.
- `sql/`: registered schema definitions; these may be PHP files despite `.sql` names.
- `patches/`: guarded installation/upgrade hooks.
- `tests/`: module-specific checks and fixtures; read prerequisites first.

Use the Chisimba `init()` lifecycle, entry-point guard, object loading and URI
helpers. Reuse `getObject`/`newObject` and existing composition boundaries. Keep
controllers small, domain behaviour in services, persistence in repositories and
HTML in templates. Preserve licences and attribution, and explain non-obvious
contracts in clear comments. Follow nearby modern conventions without unrelated
reformatting or resurrecting obsolete patterns.

**Reusability is a project requirement.** Improve a shared capability rather than
copying it into another module or adding a disposable functionality layer.
Owning modules retain their domain decisions and presentation; shared services
own reusable mechanics. Avoid circular dependencies and declare actual dependencies
in `register.conf`. Do not build direct ExtJS/vendor integrations into new modules.
Prefer semantic HTML, CSS, native browser APIs and progressive enhancement.

## Skin primitives and consistent interaction

**The skin renders the canvas; modules do not invent their own design system.**
In the framework checkout read `app/skins/chisimba-reborn/README.md`,
`app/skins/chisimba-reborn/CANVAS_CONTRACT.md` and the relevant skin rules.

- Reuse the skin's forms, cards, buttons, notices, spacing and responsive layouts.
  Current examples include `chisimba-form-card`, `chisimba-form-card--wide`,
  `chisimba-form-field` and `chisimba-form-actions`. Check current behaviour before use.
- Use the established wide-main/narrow-sidebar pattern where appropriate. Avoid
  unexplained half-width forms, narrow answer inputs or large empty header areas.
  Long editable answers must remain readable; wrapping text areas often suit them.
- Shared visual fixes belong in the framework skin. If a primitive is missing,
  add a reusable primitive there and identify the coordinated framework change.
  Do not copy card/button CSS into each module, use inline magic spacing as a
  permanent solution, or patch each canvas separately.
- Canvases supply brand identity/tokens and permitted composition variations.
  They must not duplicate the maintained skin's components and behaviour.
- Use `getObject('iconservice', 'ui')` for action icons. Use consistent icon buttons,
  including destructive actions with an appropriate confirmation. Icon-only
  buttons need accessible names. Do not use a plain deletion link beside buttons.
- Adjacent buttons, Help and search controls should have consistent heights,
  alignment and shared gaps. Use available horizontal space, then wrap cleanly
  on narrow screens. Avoid stacking controls needlessly or letting them touch cards.
- Use shared focus indicators, semantic labels/headings, meaningful validation
  and status messages, keyboard operation and reduced-motion support. Dialogs and
  contextual Help close with Escape and restore focus appropriately. Drag tools
  need a keyboard-accessible alternative.
- Preserve user work. Prefer Ajax for incremental editor/builder actions when it
  prevents disruptive refreshes; keep drafts, selection and scroll where practical.
  Do not silently discard text on errors or session/token expiry. Prevent duplicate
  long-running submissions; busy indicators must accurately reflect activity.
- Provide useful feedback such as uploaded-image previews and clear save status.
  Do not expose implementation details in a user flow unless they aid a decision.

## Language, terminology and Help

Register visible strings through the language system using British English.
Use the systext-aware `code2Txt` path for `[-author-]`, `[-authors-]`,
`[-readonly-]`, `[-readonlys-]`, `[-context-]`, `[-contexts-]`, and organisation
terms. Blog/post and category names also have configurable abstractions in their
own domains; inspect existing mappings. Do not hard-code "student", "lecturer",
"course", "blog" or "category" where a configurable term is intended.
Capitalise substituted terms when they start a heading/label/sentence; display
labels must never determine internal permissions or stable identifiers.

Add or update contextual Help for every new or materially changed user journey.
Use the shared framework `help` module and a module help-content provider. Explain
steps, who can use the feature, what Save/Publish does and how to recover from
errors. Test the compact entry point, full guide and Escape behaviour. Help is
part of the feature, not a later documentation task.

## Reuse existing services

Inspect the implementation and dependency contract before calling a service.
Examples to investigate, subject to availability on the current branch:

| Capability | Existing owner/boundary |
| --- | --- |
| Users, identities, authentication, permissions | Framework `security` services and `groupadmin` services |
| Forms/editor abstraction, icons and components | Framework `htmlelements`, `ui`, skin primitives |
| Categories and tags | Framework `classification`; owning-module access provider |
| Reusable content building | `contentblocks` composition service/editor |
| Source-document parsing | `ingestservice`; neutral model plus consumer adapters |
| Dates and timezone handling | `timeanddate-service` |
| AI requests and provider configuration | Framework `ai`; domain-specific consumer and validation |
| Products, payment events and fulfilment | `payment-service`, membership/entitlement services |
| File selection, storage and delivery | Framework `filemanager` and owning-module access policy |
| Instructor biographies | Framework `userdetails` biography support |

Do not add provider credentials or independent authentication/payment/AI stacks
to a feature module. Payment browser returns are not proof of payment. AI-generated
assessment material needs validation, human review and appropriately private answer
material. Uncertain paid requests must not be retried silently.

Useful local guides include [ingestservice](ingestservice/README.md),
[payment-service](payment-service/README.md), and
[SimpleBlog implementation and checks](simpleblog/tests/README.md). Their dated
status/version sections may be historical; verify the current code. A newer
module README describes that module, not a licence to duplicate its implementation.

## Access and persistence

- Being logged in or seeing a menu does not authorise an action. Check current
  persisted ownership, role, context and visibility on every read/write/API.
- Apply those checks to downloads, previews, derivatives, feeds, search results,
  counts and sidebar blocks as well as full pages. Reusable blocks placed on a
  prelogin page must not disclose private content or offer unauthorised operations.
- Do not trust owner/context fields from the request. Public published materials
  may be publicly readable; private course content follows its actual policy.
  Student assignment uploads remain restricted to the owner, authorised teaching
  staff and administrators even when the course is public.
- Reuse canonical user/group/permission services rather than writing their tables
  directly. Do not grant students personal publishing merely because a feature
  historically allowed it; inspect the module's explicit author/assistant policy.
- Mutations require the established HTTP-method and CSRF checks. Handle token
  renewal and draft recovery without bypassing security or replaying uncertain writes.
- Use canonical database connections, safe quoting/binding, checked failures and
  transactions. Use revision checks where concurrent editors could lose work.
  Preserve stable IDs, author/date information, source references and ownership.
- Escape at rendering boundaries; use the shared rich-text sanitiser for rich
  content. Validate files through existing upload/ingest boundaries. A public URL
  or embedding in a blog must not widen access to a private file.
- Keep credentials, personal data, source documents and generated private answers
  out of logs, fixtures and commits. Use synthetic test content and authorised
  test recipients/provider modes; no unsolicited email, payment or paid AI calls.

## Installation, tests and handoff

Use normal Module Catalogue installation/update, declared dependencies, registered
language items and guarded hooks. Bump the relevant module version when necessary.
Schema changes must preserve data and be repeatable. Do not add schema creation to
ordinary page requests. Read any module-specific post-install scripts before use.
A source rename needs a deliberate route/data compatibility plan.

Run syntax/whitespace checks plus tests appropriate to the behaviour and risk.
There is no universal module test command: discover `tests/` and read the harness.
Examples on branches containing these modules:

- `php gradebook/tests/schema_upgrade_test.php`
- `php kanban/tests/kanban_contract_test.php`
- `php mcqtests/tests/chapter_quiz_generator_contract_test.php`

Database/browser tests may need explicit environment opt-ins, an installed local
runtime, supplied fixture files or container paths. Do not guess credentials or
point them at production. Verify positive and negative permissions, fresh/update
installation where relevant, duplicate submissions, stale saves and recovery.
For UI work test the actual workflow, Help, keyboard use and narrow layouts.
Inspect logs for warnings/deprecations. Do not suppress diagnostics to obtain a pass.

Preserve active user work during testing. Remove disposable records/files and
revoke temporary account grants when done. Report what changed, what was tested,
what remains unverified, and any coordinated framework/schema changes. Keep
commits scoped and state the branch. Local testing/update does not imply production
deployment permission; honour the user's authorised site and scope. Do not merge
unrelated feature work merely to share documentation or one fix.
