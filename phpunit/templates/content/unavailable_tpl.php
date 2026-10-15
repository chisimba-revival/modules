<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
// The default also covers sites where this obsolete module is unregistered.
$message = $this->getObject('language', 'language')->languageText(
    'mod_phpunit_unavailable', 'phpunit',
    'This legacy browser-based test generator is unavailable.'
);
echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
