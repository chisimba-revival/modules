<?php
/** PHPIDS was an unregistered demonstration module, not a security hook. */
$root = dirname(__DIR__);
if (file_exists($root . '/phpids')) {
    throw new RuntimeException('Retired PHPIDS module returned');
}
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php' || str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)) continue;
    $source = file_get_contents($file->getPathname());
    if (preg_match('/\b(?:IDS_Monitor|IDS_Init)\b|[\x22\x27]phpids[\x22\x27]/i', $source)) {
        throw new RuntimeException('Retired PHPIDS caller: ' . $file->getPathname());
    }
    if ($file->getFilename() === 'HTMLPurifier.php') {
        throw new RuntimeException('Duplicate module-owned HTML Purifier: ' . $file->getPathname());
    }
}
echo "PASS: PHPIDS absent, no remaining module callers or bundled HTML Purifier copies\n";
