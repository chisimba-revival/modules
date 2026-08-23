<?php

declare(strict_types=1);

$modulesRoot = dirname(__DIR__, 2);
$obsoletePackage = $modulesRoot . '/ui';

if (is_dir($obsoletePackage)) {
    fwrite(
        STDERR,
        "The UI module is framework-owned and must not be duplicated in the modules repository.\n"
    );
    exit(1);
}

fwrite(STDOUT, "Framework ownership of the UI module verified.\n");
