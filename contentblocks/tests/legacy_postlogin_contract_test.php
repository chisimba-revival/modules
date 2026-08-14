<?php
/**
 * Verify compatibility with the prelogin and postlogin Content blocks API.
 *
 * @category  Chisimba
 * @package   contentblocks
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/modules
 */
$root = dirname(__DIR__);
$db = file_get_contents($root . '/classes/dbcontentblocks_class_inc.php');
$template = file_get_contents($root . '/templates/content/manage_tpl.php');
$checks = array(
    'postlogin block listing' => str_contains($db, 'public function getBlocksArr('),
    'postlogin block lookup' => str_contains($db, 'public function getBlockById('),
    'legacy row aliases' => str_contains($db, "['blocktext']") && str_contains($db, "'content_widetext'"),
    'explicit save action' => str_contains($template, 'name="action" value="save"'),
    'explicit save scope' => str_contains($template, 'name="scope" value="<?= $e($contentblocksScope) ?>"'),
    'explicit delete action' => str_contains($template, 'name="action" value="delete"'),
);
foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
