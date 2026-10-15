<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
echo htmlspecialchars($this->getObject('language','language')->languageText('mod_oembed_unavailable','oembed','This legacy publishing service is unavailable.'), ENT_QUOTES, 'UTF-8');
