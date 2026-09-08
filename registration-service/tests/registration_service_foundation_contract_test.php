<?php
$root = dirname(__DIR__);
$read = static fn($path) => file_get_contents($root . '/' . $path);
$registration = $read('register.conf');
$pending = $read('sql/tbl_registration_service_pending.sql');
$tokens = $read('sql/tbl_registration_service_tokens.sql');
$service = $read('classes/registrationtokenservice_class_inc.php');
$workflow = $read('classes/registrationservice_class_inc.php');
$phone = $read('classes/internationalphonenumber_class_inc.php');
$updates = $read('sql/sql_updates.xml');
$identity = $read('sql/tbl_registration_service_identities.sql');
$identityService = $read('classes/registrationidentityservice_class_inc.php');
$prepareOffset = strpos($workflow, 'private function prepareAndQueueVerification');
$prepareWorkflow = $prepareOffset === false ? '' : substr($workflow, $prepareOffset, 7000);
$checks = array(
    'service identity' => str_contains($registration, 'MODULE_ID: registration-service'),
    'service dependencies' => str_contains($registration, 'DEPENDS: security')
        && str_contains($registration, 'DEPENDS: communications')
        && str_contains($registration, 'DEPENDS: legal-acceptance-service'),
    'pending outside users' => str_contains($pending, "'password_hash'")
        && !str_contains($pending, "\$tablename = 'tbl_users'"),
    'mobile pending migration' => str_contains($pending, "'mobile_number'")
        && str_contains($updates, '<name>mobile_number</name>'),
    'administrator pending summary is bounded' => str_contains($workflow, 'public function administrationSummary()')
        && str_contains($workflow, 'ORDER BY created_at ASC LIMIT 20'),
    'stale reminder is throttled and audited' => str_contains($workflow, 'public function sendAdministratorReminder')
        && str_contains($workflow, "strtotime('-24 hours')")
        && str_contains($workflow, "'registration.verification.reminded'")
        && str_contains($workflow, 'last_reminder_at'),
    'reminder timestamp is module owned' => str_contains($pending, "'last_reminder_at'")
        && str_contains($updates, '<version>1.021</version>'),
    'certificate identity retained' => str_contains($pending, "'identity_document_type'")
        && str_contains($pending, "'identity_document_number'")
        && str_contains($identity, "'document_fingerprint'")
        && str_contains($identityService, 'function saveForUser')
        && str_contains($identityService, "hash('sha256'"),
    'international mobile normalization' => str_contains($phone, "if(str_starts_with(\$compact,'0'))")
        && str_contains($phone, "'/^\\+[1-9][0-9]{7,14}$/'"),
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
    'complete transactional email' => str_contains($workflow, "'html' => \$email['html']")
        && str_contains($workflow, 'Verify email address')
        && str_contains($workflow, 'copy and paste this address')
        && str_contains($workflow, 'expires in 24 hours')
        && str_contains($workflow, 'safely ignore this email'),
    'configurable email branding' => str_contains($workflow, 'getSiteName()')
        && !str_contains($workflow, 'Chisimba account')
        && !str_contains($workflow, 'The Chisimba team')
        && !str_contains($workflow, 'font-weight:bold">Chisimba'),
    'atomic verification state' => str_contains($workflow, 'consumeWith(')
        && str_contains($workflow, "'status' => 'verified'"),
    'resumable verification delivery' => str_contains($workflow, 'resumeVerification(')
        && str_contains($workflow, 'prepareAndQueueVerification(')
        && strpos($prepareWorkflow, "'status' => 'awaiting_verification'")
            < strpos($prepareWorkflow, 'queueEmail('),
    'no legacy workflow reuse' => !str_contains($workflow, 'userregistration')
        && !str_contains($workflow, 'useradmin_model'),
    'verified canonical provisioning' => str_contains(
        $workflow,
        'createLocalUserWithPasswordHash('
    ) && str_contains($workflow, "'howCreated' => 'registration-service'")
      && str_contains($workflow, "'cellnumber' => (string) (\$pending['mobile_number'] ?? '')"),
    'identity follows canonical provisioning' => str_contains($workflow, 'saveForUser(')
        && str_contains($workflow, 'identity_document_save_failed')
        && str_contains($workflow, 'savePendingIdentityIfPresent')
        && str_contains($workflow, "if (\$type === '' && \$number === '') { return true; }"),
    'provisioning rechecks identity' => str_contains(
        $workflow,
        'canonical_identity_conflict'
    ),
    'pending credential cleared' => str_contains(
        $workflow,
        "'password_hash' => null"
    ),
    'privacy preserving recovery' => str_contains(
        $workflow,
        "'code' => 'recovery_request_received'"
    ) && str_contains($workflow, 'findByEmail('),
    'recovery through outbox' => str_contains(
        $workflow,
        "'password-recovery:'"
    ),
    'atomic password recovery' => str_contains(
        $workflow,
        "consumeWith(\n            'password_recovery'"
    ) && str_contains($workflow, 'replaceWithinTransaction('),
    'recovery audit' => str_contains(
        $workflow,
        'account.password.recovery.completed'
    ),
    'runtime database quoting' => !str_contains($workflow . $service, '_objDB')
        && substr_count($workflow . $service, 'objEngine->getDbObj()') === 2,
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " registration foundation contract checks\n";
?>
