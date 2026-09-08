<?php
/** Course landing content contract. */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$renderer = file_get_contents($root . '/classes/contentblockbase_class_inc.php');
$template = file_get_contents($root . '/templates/content/manage_tpl.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'course text uses the rich text editor' => str_contains($template, 'value="coursetext"')
        && str_contains($template, "newObject('htmlarea', 'htmlelements')"),
    'welcome video has caption transcript and orientation' => str_contains($template, 'value="welcomevideo"')
        && str_contains($template, 'name="video_caption"')
        && str_contains($template, 'name="video_transcript"')
        && str_contains($template, 'name="video_orientation"'),
    'welcome video accepts safe supported media URLs' => str_contains($controller, "array('videohero', 'welcomevideo')")
        && str_contains($renderer, 'recognisedVideoEmbedUrl($image)'),
    'welcome video renders independently of chapters' => str_contains($renderer, 'content-block--welcome-video')
        && !str_contains($controller, 'chapterid'),
    'visible labels use system text abstractions' => str_contains($register, '[-CONTEXT-] text')
        && str_contains($register, '[-context-] landing page'),
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
