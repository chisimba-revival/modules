<?php
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fallbacks=array(
    'check_email_queued_intro'=>'A verification email is being sent to:',
    'check_email_resend'=>'Send the verification email again',
    'check_email_resend_help'=>'If it does not arrive within a few minutes, check your spam folder or send it again. Your registration and payment are safely recorded.',
);
$t = fn($k) => $this->objLanguage->languageText(
    'mod_registration_service_' . $k,
    'registration-service',
    $fallbacks[$k]??null
);
$retryAction=$e(html_entity_decode($this->uri(array('action'=>'retryverification'),'registration-service'),ENT_QUOTES,'UTF-8'));
?>
<main class="chisimba-workspace registration-service chisimba-status-page" aria-labelledby="check-email-title">
    <section class="chisimba-status-card chisimba-status-card--email" role="status">
        <div class="chisimba-status-card__icon" aria-hidden="true"></div>
        <h1 id="check-email-title"><?php echo $e($t('check_email_title')); ?></h1>
        <p class="chisimba-status-card__lead"><?php echo $e($t('check_email_queued_intro')); ?></p>
        <p class="chisimba-status-card__detail"><strong><?php echo $e($registrationEmail ?? ''); ?></strong></p>
        <p class="chisimba-status-card__detail"><?php echo $e($t('check_email_username')); ?> <strong><?php echo $e($registrationUsername ?? ''); ?></strong></p>
        <p class="chisimba-status-card__help"><?php echo $e($t('check_email_help')); ?></p>
        <form method="post" action="<?php echo $retryAction; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $e($verificationRetryCsrf ?? ''); ?>">
            <button class="button chisimba-button-secondary" type="submit"><?php echo $e($t('check_email_resend')); ?></button>
        </form>
        <p class="chisimba-status-card__help"><?php echo $e($t('check_email_resend_help')); ?></p>
    </section>
</main>
