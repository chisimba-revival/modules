# Reusable page links

Pages offers administrators **Create a reusable link** in the saved page's settings when Short URL is installed. The destination uses the page ID, so a later slug/title change does not invalidate the redirect. Publish the page before sharing: public page policy still rejects drafts and archived targets.

Use `go/bird-name/` as the short path to create `https://your-site/go/bird-name/`. Save retains the chosen path until an administrator changes it. Redirects use HTTP 302 with no-store so a printed link can be pointed at new content later. Trailing-slash variants are accepted for simple names and tracking queries do not change the destination. Unknown mappings return 404.

The deployment needs a persistent web-server route. For a root-mounted Apache installation, add this **after** existing migration rules, inside its virtual host with RewriteEngine enabled:

```apache
RewriteRule ^/go/([a-z0-9]+(?:-[a-z0-9]+)*)/?$ /index.php?module=shorturl&action=redirect [PT,L]
```

Adapt both paths for subdirectory installations. Do not replace existing WordPress/QR-code routes. Register or update Short URL through Module Catalogue and validate Apache configuration before reloading it.

Pages also has independent **Show site header and navigation** and **Show site footer** settings, stored with the composition. They affect public views only; the administration interface remains available. Existing pages default to showing both. Page title visibility is separate; audio titles and attribution remain authored content.

Checks: `php tests/public_redirect_test.php`, `php tests/security_test.php`, `php tests/controller_test.php`, `node tests/editor_test.js`, and the Pages display/public-entry/policy suites. Deployment must verify an actual anonymous redirect and preserve original published QR-code URLs.
