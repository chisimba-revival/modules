<?php
/**
 * Verify that semantic Content blocks outrank legacy skin card selectors.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   contentblocks
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/modules
 */

$root = dirname(__DIR__);
$css = file_get_contents($root . '/resources/contentblocks.css');
$register = file_get_contents($root . '/register.conf');

$checks = array(
    'module version records presentation fix' => str_contains(
        $register,
        'MODULE_VERSION: 1.007'
    ),
    'Hero uses a compound semantic selector' => str_contains(
        $css,
        '.content-block.content-block--hero {'
    ),
    'Information uses a compound semantic selector' => str_contains(
        $css,
        '.content-block.content-block--information {'
    ),
    'legacy important padding is neutralised' => substr_count(
        $css,
        'padding: 0 !important;'
    ) >= 2,
    'Hero retains the Kenga gradient' => str_contains(
        $css,
        'background: linear-gradient(135deg, #285b57, #5fbd6b);'
    ),
    'Hero title and body retain inverse colour' => str_contains(
        $css,
        '.content-block.content-block--hero .content-block__title,'
    ) && str_contains($css, 'color: inherit;'),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
