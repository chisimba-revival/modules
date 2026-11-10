# Public photo galleries

`photogallery` stores normalised, public provider snapshots. Public requests only read snapshots; they never consume provider API quota or replace a cache. Photo URLs, credits and photographer links are retained. Images remain on their providers, load lazily and open the source photo. The shared skin supplies responsive cards. Pagination is 12 items per page.

Flickr uses the public album feed, which supplies a limited selection (currently 20). Pexels uses an explicitly configured photographer profile and bounded search terms, matching the profile exactly; it does not claim to enumerate every upload. Searches are capped at 14 terms, two pages each, 80 matches retained and a 30-second between-request time budget. A request can add at most 12 seconds. This matches the source plugin's search approach while avoiding accidental prefix matches to other photographers.

Source URLs and settings are administrator/importer-owned data, never accepted from public query parameters. Provider HTTP requests use fixed HTTPS endpoints, no redirects, verified TLS, finite timeouts and a 4 MB response limit. Only normalised data enters the public cache. Never store credentials in gallery settings: the Pexels key belongs in the shared system configuration and is read server-side only. The source WordPress API keys were not copied.

Normal module catalogue installation creates the cache table and language terms. The migration importer seeds the two galleries repeatably and refuses to replace changed records. Existing cached source data is identified as a migration snapshot, not a fresh Pexels API fetch.

Provider refresh defaults to disabled. Enable PHOTO_REFRESH_ENABLED only in a reviewed environment with connectivity; Pexels also requires PEXELS_API_KEY in shared system configuration. A site administrator can then explicitly refresh using a CSRF-protected POST. Empty, invalid or failed provider responses preserve the previous snapshot. Refresh is manual in this iteration, not scheduled. The preview keeps its internal network and disabled refresh setting. Tests use fake providers and do not exercise authenticated Pexels refresh.

Reference API contracts: https://www.pexels.com/api/documentation/ and https://www.flickr.com/services/api/misc.urls.html . Flickr source is the public album feed selected by the WordPress widget.
