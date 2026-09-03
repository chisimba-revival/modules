<?php
/** Contract checks for discoverable Discussion administration. */
$register = file_get_contents(__DIR__ . '/../register.conf');
$admin = file_get_contents(__DIR__ . '/../classes/block_discussionadmin_class_inc.php');
$checks = array(
    'uses current product name' => str_contains($register, 'MODULE_NAME: Discussion'),
    'declares an administration page' => str_contains($register, 'MODULE_HASADMINPAGE: 1'),
    'shared admin entry targets management' => str_contains($register, 'PAGE: admin_shared|administration|messages-square|mod_discussion_discussionadministration|site'),
    'lecturers get a course-scoped management entry' => str_contains($register, 'PAGE: lecturer_tools|administration|messages-square|mod_discussion_discussionadministration|context'),
    'does not duplicate the shared admin action in a side menu' => !str_contains($register, 'SIDEMENU: postlogin-3|Site Admin|administration'),
    'navigation reuses registered language label' => str_contains($register, 'TEXT: mod_discussion_discussionadministration|Forum Administration|Forum Administration'),
    'Derek Keats remains an author' => preg_match('/^MODULE_AUTHORS:.*Derek Keats/m', $register) === 1,
    'admin interface uses responsive cards' => str_contains($admin, 'buildModernAdmin') && str_contains($admin, 'discussion-admin-grid') && str_contains($admin, 'discussion-admin-card'),
    'admin interface uses skin actions and icons' => str_contains($admin, "getObject('iconservice', 'ui')") && str_contains($admin, 'chisimba-form-actions') && str_contains($admin, 'chisimba-button-danger'),
    'card actions use accessible skin icon buttons' => str_contains($admin, 'class="chisimba-icon-button"') && str_contains($admin, 'aria-label="Open ') && str_contains($admin, "render('save'"),
    'empty and default states remain distinct cards' => str_contains($admin, 'discussion-admin-default--empty') && str_contains($admin, 'discussion-empty-state__icon'),
    'modern admin path has no layout table' => !str_contains(substr($admin, strrpos($admin, 'private function buildModernAdmin')), 'htmlTable'),
    'legacy block title is suppressed' => str_contains($admin, "\$this->title = ''"),
    'Lobby administration uses an explicit bounded scope' => str_contains($admin, "\$this->contextCode = 'root';") && str_contains($admin, 'getAllContextDiscussions($this->contextCode)'),
);
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS: ' : 'FAIL: ') . $label . PHP_EOL;
    if (!$passed) { exit(1); }
}
