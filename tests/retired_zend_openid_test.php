<?php
$root = dirname(__DIR__);
foreach (['zend/resources/Zend/OpenId', 'zend/resources/Zend/OpenId.php', 'zend/resources/Zend/Auth/Adapter/OpenId.php'] as $path) {
    if (file_exists($root . '/' . $path)) throw new RuntimeException('Retired Zend OpenID returned: ' . $path);
}
echo "PASS: unused Zend OpenID consumer, provider and authentication adapter absent\n";
