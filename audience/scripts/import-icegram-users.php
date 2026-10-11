<?php
/** Retired one-off LearnTheBirds staging migration. See docs/retired-staging-imports.md.
 * Original implementation remains in Git history. Never bootstrap or mutate a site.
 * @author Derek Keats
 */
if (PHP_SAPI !== 'cli') { http_response_code(410); exit; }
fwrite(STDERR, "Retired LearnTheBirds staging migration. See modules/docs/retired-staging-imports.md. No changes made.\n");
exit(64);
