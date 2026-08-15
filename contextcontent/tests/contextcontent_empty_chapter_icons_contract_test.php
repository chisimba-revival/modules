<?php
/**
 * Verify semantic action icons on the empty-chapter view.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   contextcontent
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/modules
 */

$root = dirname(__DIR__);
$template = file_get_contents(
    $root . '/templates/content/chapternocontent_tpl.php'
);
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'manifest records the icon repair' => str_contains(
        $register,
        'UPDATE_DESCRIPTION: Replaced empty-chapter legacy action icons with accessible Lucide icons'
    ),
    'empty chapter uses the icon service' => str_contains(
        $template,
        "getObject('iconservice', 'ui')"
    ),
    'edit action uses pencil' => str_contains(
        $template,
        "render('pencil'"
    ),
    'delete action uses trash' => str_contains(
        $template,
        "render('trash-2'"
    ),
    'add-page action uses plus' => str_contains(
        $template,
        "render('plus'"
    ),
    'legacy icon producer is absent' => !str_contains(
        $template,
        "newObject('geticon'"
    ),
    'icon-only actions retain accessible labels' => substr_count(
        $template,
        'aria-label='
    ) === 3,
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
