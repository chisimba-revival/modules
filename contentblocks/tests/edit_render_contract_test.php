<?php
/**
 * Verify Content blocks management markup and legacy placement rendering.
 *
 * @category  Chisimba
 * @package   contentblocks
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/modules
 */
$root = dirname(__DIR__);
$base = file_get_contents($root . '/classes/contentblockbase_class_inc.php');
$template = file_get_contents($root . '/templates/content/manage_tpl.php');
$checks = array(
    'render by block key' => str_contains($base, 'findByKey($blockKey)'),
    'render legacy placement by id' => str_contains($base, 'find($blockKey)'),
    'delete action is explicit' => str_contains($template, 'name="action" value="delete"'),
    'delete confirmation tag closes before fields' => preg_match(
        '~onsubmit="return confirm\([^\n]+\)">\n\s*<input type="hidden" name="action" value="delete">~',
        $template
    ) === 1,
    'edit link is closed' => preg_match('~<a href="<\?= .*? \?>"><\?= \$e\(\$l\[\x27edit\x27\]\) \?></a>~', $template) === 1,
    'management save control remains intact' => str_contains($template, '<button type="submit"><?= $e($l[\'save\']) ?></button>'),
    'save action is explicit' => str_contains($template, 'name="action" value="save"'),
);
foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
