<?php
/**
 * Verify the dedicated TikTok learning-content page contract.
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
$registry = file_get_contents($root . '/classes/contenttyperegistry_class_inc.php');
$service = file_get_contents($root . '/classes/tiktokvideoservice_class_inc.php');
$controller = file_get_contents($root . '/controller.php');
$form = file_get_contents($root . '/templates/content/addeditpage_tpl.php');
$view = file_get_contents($root . '/templates/content/viewpage_tpl.php');
$register = file_get_contents($root . '/register.conf');

$checks = array(
    'version records the new page type' => str_contains($register, 'MODULE_VERSION: 2.052'),
    'TikTok is an independent registered page type' => str_contains($registry, "'key' => 'tiktok_video'"),
    'palette uses an available semantic icon' => str_contains($registry, "'icon' => 'smartphone'"),
    'service accepts canonical post URLs' => str_contains($service, "video/([0-9]{10,24})"),
    'service allow-lists TikTok hosts' => str_contains($service, "substr(\$host, -11) !== '.tiktok.com'"),
    'service emits the official hosted player' => str_contains($service, "https://www.tiktok.com/player/v1/"),
    'arbitrary author iframe markup is never accepted' => !str_contains($form, 'tiktok_embed_html'),
    'new and edited pages use the same service' => substr_count($controller, 'tiktokBodyFromRequest()') === 3,
    'authoring form retains URL caption and transcript' => str_contains($form, "tiktokField('tiktok_url'")
        && str_contains($form, "tiktokField('tiktok_caption'")
        && str_contains($form, "tiktokField('tiktok_transcript'"),
    'viewer treats only generated TikTok markup as trusted' => str_contains(
        $view,
        "'video', 'tiktok_video', 'pdf'"
    ),
    'portrait player has a nine-by-sixteen ratio' => str_contains($view, 'aspect-ratio:9/16'),
    'all visible labels are registered' => str_contains($register, 'mod_contextcontent_tiktok_guidance')
        && str_contains($register, 'mod_contextcontent_tiktok_invalid_url'),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
