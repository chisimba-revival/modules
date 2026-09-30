<?php
/** Keep the current publishing Help terminology configurable. */
$registration = file_get_contents(dirname(__DIR__).'/register.conf');
foreach (explode("\n", $registration) as $line) {
    if (!str_starts_with($line, 'TEXT: mod_simpleblog26_')) continue;
    $parts = explode('|', $line, 3);
    $text = preg_replace('/\[-[^]]+-\]/', '', $parts[2] ?? '');
    if (preg_match('/\b(?:blogs?|posts?)\b/i', $text)) {
        throw new RuntimeException('Unabstracted publishing terminology: '.$parts[0]);
    }
}
$help = file_get_contents(dirname(__DIR__).'/classes/helpcontent_class_inc.php');
if (!str_contains($help, "text('access_help')")) {
    throw new RuntimeException('Publishing Help must include access guidance');
}
$renderer = file_get_contents(dirname(__DIR__).'/classes/publishingrenderer_class_inc.php');
if (!str_contains($renderer, "code2Txt('mod_simpleblog26_'")) {
    throw new RuntimeException('Publishing text must resolve system terminology');
}
echo "PASS: publishing terminology tokens and access Help integration\n";
