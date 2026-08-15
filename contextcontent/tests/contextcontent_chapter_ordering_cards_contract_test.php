<?php
/**
 * Verify secure chapter ordering and semantic chapter cards.
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
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/listchapters_tpl.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'manifest records restoration' => str_contains($register, 'Restored secure chapter ordering'),
    'chapter list receives a CSRF token' => str_contains($controller, "isValid('movechapterup')")
        && str_contains($controller, '$this->prepareMutationForm();'),
    'ordering uses POST forms' => str_contains($template, 'method="post"')
        && str_contains($template, "name=\"csrf_token\"")
        && str_contains($template, "name=\"id\""),
    'ordering controls have no legacy slash divider' => !str_contains($template, '$divider = \' / \'')
        && str_contains($template, 'chisimba-chapter-order-actions'),
    'both directions are restored' => str_contains($template, "'movechapterup'")
        && str_contains($template, "'movechapterdown'"),
    'boundary checks remain' => str_contains($template, '$counter > 1')
        && str_contains($template, '$counter < (is_countable($chapters)'),
    'empty ordering stubs are gone' => !str_contains($template, 'new stdClass()')
        && !str_contains($template, 'Reordering is exposed only'),
    'chapters use semantic sections' => str_contains($template, '<section class="chapterlisting">')
        && !str_contains($template, "</div><hr />';"),
    'icon buttons are accessible' => str_contains($template, 'chisimba-chapter-order-button')
        && str_contains($template, 'aria-label='),
    'ordering uses registered icons' => str_contains($template, "render('chevron-left'")
        && str_contains($template, "render('chevron-right'")
        && !str_contains($template, "render('arrow-up'")
        && !str_contains($template, "render('arrow-down'"),
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
