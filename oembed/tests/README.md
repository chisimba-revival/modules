# Native media consumer checks

Run `php oembed/tests/native_embed_test.php` from the modules repository root.
It uses the sibling framework checkout for filter code; set
`CHISIMBA_FRAMEWORK_ROOT` to the installed app root when running inside a runtime.
No database, external requests, media playback or email is involved.

The test covers shared media recognition, hostile/malformed URLs, link escaping,
loader deduplication, quickembed registration gating, resolver method handling
and HTTP 410 for old publishing actions. Run the framework's
`jquerycore/tests/plugin_retirement_test.php` too, and `node --check` on
`resources/nativeembed.js` and `../wall/resources/wall.js`.

Browser verification uses a disposable local fixture: no jQuery, overlapping
scopes, a synthetic newly inserted reply, an unsupported link, and an existing
local PNG. Confirm one button per supported link, no pre-click media request,
keyboard activation/focus, original-link retention and narrow layouts. Test the
actual demo's contextual Help, full guide and Escape. Do not enable Wall or
exercise its legacy write endpoints just to test media consumption.

The resolver classifies URL strings only; it never fetches pages, parses remote
HTML, reads file metadata, or makes stored content public. Provider media loads
in the browser only after activation. Unsupported URLs remain links.
