<?php
$root=dirname(__DIR__);
if (file_exists($root.'/resources/ChisimbaLangTranslator')
    || str_contains(file_get_contents($root.'/templates/content/home_tpl.php'),'ChisimbaLangTranslator')) {
    throw new RuntimeException('Retired desktop translation client returned');
}
echo "PASS desktop translation client and download link retired\n";
