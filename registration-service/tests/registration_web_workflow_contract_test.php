<?php
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$register = file_get_contents($root . '/register.conf');
$templates = '';
foreach (glob($root . '/templates/content/*.php') as $file) {
    $templates .= file_get_contents($file);
}
$checks = array(
    'hyphenated controller alias' => str_contains($controller, "class_alias('registration_service', 'registration-service')"),
    'public action allow-list' => str_contains($controller, "'register', 'verify', 'terms'")
        && str_contains($controller, "'forgotpassword', 'requestrecovery', 'recover', 'resetpassword'"),
    'no legacy reuse' => !str_contains($controller . $templates . $register, 'userregistration'),
    'canonical orchestration' => str_contains($controller, 'createPending(')
        && str_contains($controller, 'acceptLegalAndQueueVerification(')
        && str_contains($controller, 'completeEmailVerification(')
        && str_contains($controller, 'provisionVerified('),
    'recovery orchestration' => str_contains($controller, 'requestPasswordRecovery(')
        && str_contains($controller, 'completePasswordRecovery('),
    'csrf contexts' => substr_count($controller, "->consume(") === 3
        && substr_count($controller, "->issue(") === 3,
    'abuse protection' => str_contains($controller, "issueFormEvidence('registration.create')")
        && str_contains($controller, "issueFormEvidence('registration.recovery')")
        && str_contains($controller, "'website' =>"),
    'passwords never repopulated' => !str_contains($controller, "'password' => \$this->scalarParam")
        && !str_contains($templates, 'value="<?php echo $e($password'),
    'safe URI rendering' => str_contains($templates, 'html_entity_decode($this->uri(')
        && !str_contains($templates, '$e($this->uri('),
    'skin primitives' => str_contains($templates, 'chisimba-workspace')
        && str_contains($templates, 'chisimba-form-section')
        && str_contains($templates, 'chisimba-form-actions'),
    'accessible forms' => str_contains($templates, 'autocomplete="new-password"')
        && str_contains($templates, 'role="alert"')
        && str_contains($templates, 'aria-labelledby='),
    'privacy-preserving recovery copy' => str_contains($register, 'If an active account uses that address'),
    'module update' => str_contains($register, 'MODULE_VERSION: 1.001'),
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
echo 'OK: ' . count($checks) . " registration web workflow checks\n";
?>
