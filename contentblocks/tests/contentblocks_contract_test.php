<?php
/**
 * Content blocks module component.
 *
 * This file forms part of the Chisimba Content blocks module.
 *
 * PHP version 8
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * @category  Chisimba
 * @package   contentblocks
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/modules
 */
$root = dirname(__DIR__);
$register = file_get_contents($root . '/register.conf');
$base = file_get_contents($root . '/classes/contentblockbase_class_inc.php');
$controller = file_get_contents($root . '/controller.php');
$db = file_get_contents($root . '/classes/dbcontentblocks_class_inc.php');
$checks = array(
    'context aware' => str_contains($register, 'CONTEXT_AWARE: 1'),
    'no context dependency' => str_contains($register, 'DEPENDS_CONTEXT: 0') && !preg_match('/^DEPENDS: *context$/m', $register),
    'two block types' => str_contains($controller, "array('hero','information')"),
    'render context guard' => str_contains($base, "=== 'context'") && str_contains($base, 'currentContext()'),
    'safe semantic rendering' => str_contains($base, "'blockType' => 'none'") && str_contains($base, 'washout'),
    'csrf guard' => str_contains($controller, 'hash_equals'),
    'dbTable init signature' => str_contains($db, 'public function init($tableName = null, $pearDb = NULL, $errorCallback = "globalPearErrorCallback")')
        && str_contains($db, 'parent::init(\'tbl_contentblocks\', $pearDb, $errorCallback)'),
    'manifest author' => str_contains($register, 'MODULE_AUTHORS: Derek Keats'),
    'clear placement labels' => str_contains($register, 'TEXT: mod_contentblocks_wide|Wide main-content label|Wide (main content)')
        && str_contains($register, 'TEXT: mod_contentblocks_normal|Side-column label|Side column'),
    'context plugin declaration' => str_contains($register, 'ISCONTEXTPLUGIN: 1'),
    'lecturer manifest rule' => str_contains($register, 'CONDITION: iscontextlecturer|Lecturers')
        && str_contains($register, 'RULE: manage,save,delete|iscontextlecturer'),
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
