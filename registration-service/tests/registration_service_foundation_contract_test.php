<?php
$root = dirname(__DIR__);
$read = static fn($path) => file_get_contents($root . '/' . $path);
$registration = $read('register.conf');
$pending = $read('sql/tbl_registration_service_pending.sql');
$tokens = $read('sql/tbl_registration_service_tokens.sql');
$service = $read('classes/registrationtokenservice_class_inc.php');
$workflow = $read('classes/registrationservice_class_inc.php');
$checks = array(
    'service identity' => str_contains($registration, 'MODULE_ID: registration-service'),
    'service dependencies' => str_contains($registration, 'DEPENDS: security')
        && str_contains($registration, 'DEPENDS: communications')
        && str_contains($registration, 'DEPENDS: legal-acceptance-service'),
    'pending outside users' => str_contains($pending, "'password_hash'")
        && !str_contains($pending, "\$tablename = 'tbl_users'"),
    'hash width' => str_contains($pending, "'length' => 255"),
    'hashed verifier' => str_contains($tokens, "'verifier_hash'")
        && str_contains($service, "hash('sha256', \$rawVerifier)"),
    'single use' => str_contains($tokens, "'consumed_at'")
        && str_contains($service, 'FOR UPDATE'),
    'atomic state transition' => str_contains($service, 'consumeWith(')
        && str_contains($service, 'token_transition_failed'),
    'supersession' => str_contains($tokens, "'superseded_at'")
        && str_contains($service, 'token_issued'),
    'bounded lifetime' => str_contains($service, "'max_range' => 604800"),
    'purpose subject binding' => str_contains($service, "email_verification' && \$subjectType")
        && str_contains($service, "password_recovery' && \$subjectType"),
    'no legacy dependency' => !str_contains($registration . $service, 'userregistration'),
    'no canonical user write' => !str_contains($workflow, "tbl_users")
        && str_contains($workflow, "'userservice', 'security'"),
    'legal gate' => str_contains($workflow, 'hasAccepted(')
        && str_contains($workflow, 'legal_acceptance_not_confirmed'),
    'outbox delivery' => str_contains($workflow, 'queueEmail(')
        && str_contains($workflow, 'idempotencyKey'),
    'atomic verification state' => str_contains($workflow, 'consumeWith(')
        && str_contains($workflow, "'status' => 'verified'"),
    'no legacy workflow reuse' => !str_contains($workflow, 'userregistration')
        && !str_contains($workflow, 'useradmin_model'),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " registration foundation contract checks\n";
?>
