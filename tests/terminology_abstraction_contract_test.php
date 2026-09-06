<?php
/** Prevent literal role and context names returning to active module language defaults. */
$root = dirname(__DIR__);
$modules = array('mylearning','myteaching','myadmin','contextcontent','announcements','payment-service','registration-service','membership-service','essay','mcqtests','rubric','gradebook','contentblocks','discussion');
$failures = array();
foreach ($modules as $module) {
    $path = $root . '/' . $module . '/register.conf';
    if (!is_file($path)) continue;
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: array() as $number => $line) {
        if (!preg_match('/^(?:TEXT|USES):\s*([^|]+)\|[^|]*\|(.*)$/', $line, $match)) continue;
        $withoutTokens = preg_replace('/\[-(?:author|authors|readonly|readonlys|context|contexts|organisation|organisations)-\]/i', '', $match[2]);
        if (preg_match('/\b(?:course|courses|student|students|lecturer|lecturers|learner|learners)\b/i', $withoutTokens)) {
            $failures[] = $module . '/register.conf:' . ($number + 1) . ': ' . $match[2];
        }
    }
}
if ($failures) {
    fwrite(STDERR, "Hard-coded role or context terminology found:\n" . implode("\n", $failures) . "\n");
    exit(1);
}
fwrite(STDOUT, "PASS: active module language defaults preserve system terminology.\n");
