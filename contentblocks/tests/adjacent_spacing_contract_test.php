<?php
/**
 * Verify the logical gap between adjacent semantic Content blocks.
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

$css = file_get_contents(
    dirname(__DIR__) . '/resources/contentblocks.css'
);
$checks = array(
    'Hero adjacency selector exists' => str_contains(
        $css,
        '.content-block.content-block--hero + .content-block,'
    ),
    'Information adjacency selector exists' => str_contains(
        $css,
        '.content-block.content-block--information + .content-block {'
    ),
    'small logical gap outranks canvas reset' => str_contains(
        $css,
        'margin-block-start: 0.75rem !important;'
    ),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
