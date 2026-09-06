<?php
$base = dirname(__DIR__);
$provider = file_get_contents($base . '/classes/helpcontent_class_inc.php');
$register = file_get_contents($base . '/register.conf');
$templates = file_get_contents($base . '/templates/content/main_tpl.php')
    . file_get_contents($base . '/templates/content/create_tpl.php')
    . file_get_contents($base . '/templates/content/edit_tpl.php');
$checks = array(
    strpos($provider, 'isLecturer()') !== false && strpos($provider, 'isAdmin()') !== false,
    substr_count($templates, "show('rubric', 'creating-and-editing-rubrics')") === 3,
    strpos($register, 'DEPENDS: help') !== false,
    strpos($register, 'Essays use a supplied default') !== false,
    strpos($register, 'Worksheet AI marking uses its default') !== false,
    strpos($register, '[-author-]') !== false,
    strpos($register, '[-context-]') !== false,
);
if (in_array(false, $checks, true)) { fwrite(STDERR, "Rubric contextual Help contract failed.\n"); exit(1); }
echo "Rubric contextual Help contract passed.\n";
?>
