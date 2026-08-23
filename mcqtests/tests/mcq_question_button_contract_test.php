<?php
/**
 * Source contract for skin-neutral MCQ question form actions.
 *
 * The module owns semantic action markup and Lucide icon choice. Maintained
 * skins own only presentation, so Chisimba Reborn and Kenga Learn cannot drift
 * back to legacy sexybutton decoration on this form.
 */
$template = dirname(__DIR__) . '/templates/content/addquestion_tpl.php';
$source = file_get_contents($template);
if ($source === false) {
    fwrite(STDERR, "FAIL: could not read addquestion template\n");
    exit(1);
}

$checks = array(
    'uses shared icon service' => "getObject('iconservice', 'ui')",
    'save uses circle-check icon' => "render('circle-check'",
    'cancel uses x icon' => "render('x'",
    'uses skin-neutral action group' => 'mcq-question-form-actions',
    'uses native save button' => '<button class="button" type="submit"',
    'uses secondary cancel button' => 'chisimba-button-secondary',
);

foreach ($checks as $label => $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: $label\n");
        exit(1);
    }
    echo "PASS: $label\n";
}

$activeSource = preg_replace('#/\\*.*?\\*/#s', '', $source);
if (preg_match('/new\\s+button\\s*\\(/i', (string) $activeSource)) {
    fwrite(STDERR, "FAIL: active question form still uses legacy button helper\n");
    exit(1);
}

echo "PASS: MCQ question form actions are native, icon-consistent and skin-neutral\n";
