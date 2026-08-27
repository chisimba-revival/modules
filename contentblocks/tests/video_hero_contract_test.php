<?php
/**
 * Video Hero and compact side-column regression contract.
 *
 * @category Tests
 * @package  contentblocks
 * @author   Derek Keats
 */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$renderer = file_get_contents($root . '/classes/contentblockbase_class_inc.php');
$template = file_get_contents($root . '/templates/content/manage_tpl.php');
$css = file_get_contents($root . '/resources/contentblocks.css');
$register = file_get_contents($root . '/register.conf');

$checks = array(
    'Video Hero is a selectable type' => str_contains($template, 'value="videohero"')
        && str_contains($register, 'Video Hero type label|Video Hero'),
    'Video Hero is always wide' => str_contains($controller, "elseif (\$type === 'videohero')")
        && str_contains($controller, "\$width = 'wide';"),
    'Video Hero requires video' => str_contains($controller, "\$type === 'videohero' && \$image === ''"),
    'Video Hero has no visible copy or chrome' => str_contains($renderer, '<video class="content-block content-block--video-hero"')
        && str_contains($css, '.content-block.content-block--video-hero')
        && str_contains($css, 'background: transparent'),
    'Video picker is constrained' => str_contains($template, "'policy' => 'video'")
        && str_contains($template, 'name="video_url"'),
    'narrow Information spacing is compact' => str_contains($renderer, 'content-block--\' . $placement')
        && str_contains($css, '.content-block.content-block--information.content-block--normal .content-block__inner')
        && str_contains($css, 'padding: 1rem'),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
