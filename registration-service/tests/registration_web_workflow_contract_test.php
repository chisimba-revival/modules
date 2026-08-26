<?php
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$register = file_get_contents($root . '/register.conf');
$layout = file_get_contents($root . '/templates/layout/registration_layout_tpl.php');
$templates = '';
foreach (glob($root . '/templates/content/*.php') as $file) {
    $templates .= file_get_contents($file);
}
$checks = array(
    'hyphenated controller alias' => str_contains($controller, "class_alias('registration_service', 'registration-service')"),
    'public action allow-list' => str_contains($controller, "'register', 'verify', 'terms'")
        && str_contains($controller, "'forgotpassword', 'requestrecovery', 'recover', 'resetpassword'"),
    'standard two-column layout' => str_contains($controller, "setLayoutTemplate('registration_layout_tpl.php')")
        && str_contains($layout, "newObject('csslayout', 'htmlelements')")
        && str_contains($layout, 'setNumColumns(3)')
        && str_contains($layout, "layoutType = 'canvas_stacked31'")
        && str_contains($layout, 'setMiddleColumnContent(')
        && str_contains($layout, 'setRightColumnContent(')
        && str_contains($layout, 'chisimba-guidance-card'),
    'contextual recovery guidance' => str_contains($controller, "'recovery_reset_guidance'")
        && str_contains($controller, "'recovery_guidance'")
        && str_contains($layout, '$registrationGuidancePrefix')
        && str_contains($register, 'Resetting your password')
        && str_contains($register, 'Finish resetting your password'),
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
    'sensitive response policy' => str_contains($controller, "Cache-Control: no-store")
        && str_contains($controller, "Referrer-Policy: no-referrer"),
    'minimum password length' => substr_count($controller, 'strlen($password) < 12') === 2
        && substr_count($templates, 'minlength="12"') === 4,
    'passwords never repopulated' => !str_contains($controller, "'password' => \$this->scalarParam")
        && !str_contains($templates, 'value="<?php echo $e($password'),
    'safe URI rendering' => str_contains($templates, 'html_entity_decode($this->uri(')
        && !str_contains($templates, '$e($this->uri('),
    'skin primitives' => str_contains($templates, 'chisimba-workspace')
        && str_contains($templates, 'chisimba-form-section')
        && str_contains($templates, 'chisimba-form-actions')
        && str_contains($templates, 'chisimba-status-card'),
    'accessible forms' => str_contains($templates, 'autocomplete="new-password"')
        && str_contains($templates, 'role="alert"')
        && str_contains($templates, 'aria-labelledby=')
        && str_contains($templates, 'aria-describedby="registration-username-help registration-username-status"')
        && str_contains($templates, 'aria-describedby="registration-password-help"'),
    'structured registration fields' => str_contains($templates, 'chisimba-form-card')
        && str_contains($templates, 'chisimba-form-grid')
        && str_contains($templates, 'chisimba-field-help'),
    'international mobile input' => str_contains($templates, 'name="country_calling_code"')
        && str_contains($templates, 'name="mobile_number"')
        && str_contains($controller, "'REGISTRATION_DEFAULT_CALLING_CODE'")
        && str_contains($register, 'CONFIG: REGISTRATION_DEFAULT_CALLING_CODE|+27'),
    'privacy-preserving recovery copy' => str_contains($register, 'If an active account uses that address'),
    'live username availability' => str_contains($controller, "case 'usernameavailability'")
        && str_contains($templates, 'registration-username-status')
        && str_contains($templates, 'first_name=')
        && str_contains($templates, 'surname='),
    'module update' => str_contains($register, 'MODULE_VERSION: 1.005'),
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
echo 'OK: ' . count($checks) . " registration web workflow checks\n";
?>
