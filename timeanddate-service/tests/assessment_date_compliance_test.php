<?php
/**
 * Prevent direct server-clock and legacy date utility use in modernised assessments.
 *
 * Calendar arithmetic through DateTimeImmutable remains valid. Application storage,
 * parsing, comparison and display must pass through timeanddateservice.
 *
 * @author Derek Keats
 */
$modulesRoot = dirname(__DIR__, 2);
$moduleNames = array('essay', 'gradebook', 'worksheet', 'mcqtests');
$violations = array();

foreach ($moduleNames as $moduleName) {
    $directory = $modulesRoot . '/' . $moduleName;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($iterator as $file) {
        $path = $file->getPathname();
        if (!$file->isFile() || $file->getExtension() !== 'php'
            || str_contains($path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)) {
            continue;
        }
        $source = file_get_contents($path);
        $tokens = token_get_all($source);
        $executable = '';
        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], array(T_COMMENT, T_DOC_COMMENT), true)) {
                continue;
            }
            $executable .= is_array($token) ? $token[1] : $token;
        }
        $checks = array(
            'direct PHP clock/date call' => '/(?<!->)(?<!::)\b(?:date|strftime|strtotime|time|mktime)\s*\(/',
            'legacy dateandtime utility' => '/[\'\"]dateandtime[\'\"]/i',
            'ambiguous datetime-local input' => '/\bdatetime-local\b/i',
            'known minutes/month format error' => '/H:[mM](?![A-Za-z])/i',
        );
        foreach ($checks as $label => $pattern) {
            if (preg_match($pattern, $executable)) {
                $violations[] = $moduleName . ': ' . $label . ': ' . $path;
            }
        }
    }
    $registration = file_get_contents($directory . '/register.conf');
    if (!str_contains($registration, 'DEPENDS: timeanddate-service')) {
        $violations[] = $moduleName . ': missing timeanddate-service dependency';
    }
}

if ($violations) {
    fwrite(STDERR, "Date compliance failures:\n" . implode("\n", $violations) . "\n");
    exit(1);
}

echo 'OK: canonical date boundaries enforced for ' . count($moduleNames) . " assessment modules\n";
?>
