# Forms paused — 16 October 2026

Preserved at Derek’s request for possible future review. This is the old Form
maker module, not Formbuilder. Do not rename, register or load it as a shortcut
to enabling it. No database content was deleted.

All six production sites and local Chisimba were unregistered, had none of the
four legacy Forms tables, and had no matching references in the inspected content
columns. This is bounded evidence, not proof about external installations.

Before revival: replace FCKeditor/raw HTML and script authoring with a defined
field model; repair permissions, POST/CSRF, public-submission validation and abuse
controls, escaping/sanitisation, bound queries and transactional parent/child
writes; preserve record identity; repair reporting; remove CMS and old jQuery
coupling; add installation, PDO and browser tests. Review the independent
Formbuilder module before creating overlapping functionality.

The shared parse4form filter is deliberately inert and preserves source text.
It must not load this directory or render old stored form HTML. Original filter
implementation remains recoverable in framework Git history.
