<?php
/** Contract checks for discoverable Discussion administration. */
$register = file_get_contents(__DIR__ . '/../register.conf');
$admin = file_get_contents(__DIR__ . '/../classes/block_discussionadmin_class_inc.php');
$checks = array(
    'uses current product name' => str_contains($register, 'MODULE_NAME: Discussion'),
    'declares an administration page' => str_contains($register, 'MODULE_HASADMINPAGE: 1'),
    'shared admin entry targets management' => str_contains($register, 'PAGE: admin_shared|administration|messages-square|mod_discussion_admin_manage|site'),
    'admin label is human readable' => str_contains($register, 'TEXT: mod_discussion_admin_manage|Manage discussions|Manage discussions'),
    'Derek Keats remains an author' => preg_match('/^MODULE_AUTHORS:.*Derek Keats/m', $register) === 1,
    'admin interface uses responsive cards' => str_contains($admin, 'buildModernAdmin') && str_contains($admin, 'discussion-admin-grid') && str_contains($admin, 'discussion-admin-card'),
    'admin interface uses skin actions and icons' => str_contains($admin, "getObject('iconservice', 'ui')") && str_contains($admin, 'chisimba-form-actions') && str_contains($admin, 'chisimba-button-danger'),
    'modern admin path has no layout table' => !str_contains(substr($admin, strrpos($admin, 'private function buildModernAdmin')), 'htmlTable'),
);
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS: ' : 'FAIL: ') . $label . PHP_EOL;
    if (!$passed) { exit(1); }
}
