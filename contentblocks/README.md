
### Social links in compositions

An editable `<ul class="chisimba-social-links">` holds ordinary labelled links.
The shared renderer adds local brand icons only to this explicitly marked list;
other links are unchanged. Edit destinations and link text in the text editor;
custom link text becomes the accessible name and tooltip. Hostname-only labels
fall back to the service name plus account path, distinguishing multiple accounts.
Unknown website hosts receive a globe. No remote icon requests or authored SVG
are permitted. The WordPress converter marks Elementor social-icon lists on import.

### Portrait video galleries

The `video_gallery` composition type is owned by its containing page or article,
so existing draft visibility, permissions, CSRF, revision checks and saving apply.
It contains an ordered collection of stable source IDs, titles, supported video
URLs, managed thumbnail URLs, source publication dates and topic labels. TikTok,
YouTube and Vimeo use the shared media parser. No provider API or thumbnail fetch
runs during a reader's request. Imported source dates are retained as calendar
values, matching the WordPress ordering rather than guessing TikTok dates.

The editor supports adding/removing entries and choosing the initial date order.
Incomplete entries remain editable but do not render. Readers can reverse the date
order, open a lazy native dialog player, close with Escape/button/backdrop, and
return to the trigger. Without JavaScript, thumbnail links still open the source.
The skin uses available collection width for one, two, three or four columns and
provides the dark backdrop. Other video-card consumers retain their existing UI.

This is an embeddable collection, not yet a standalone central video-library
administration module. WordPress import manifests can provide reviewed shortcodes
as `{type: "video_gallery", order: "asc", videos: [...]}`; thumbnail URLs pass
through the normal managed-media replacement map. Legacy reviewed video lists
remain supported for earlier manifests.

Validation: `php contentblocks/tests/video_collection_test.php` from the modules
repository; use `CHISIMBA_FRAMEWORK_ROOT` if the framework is not its sibling.
