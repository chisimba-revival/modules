<?php
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$t = fn($k) => $this->objLanguage->languageText(
    'mod_registration_service_' . $k,
    'registration-service'
);
?>
<main class="chisimba-workspace registration-service chisimba-status-page" aria-labelledby="check-email-title">
    <section class="chisimba-status-card chisimba-status-card--email" role="status">
        <div class="chisimba-status-card__icon" aria-hidden="true"></div>
        <h1 id="check-email-title"><?php echo $e($t('check_email_title')); ?></h1>
        <p class="chisimba-status-card__lead"><?php echo $e($t('check_email_intro')); ?></p>
        <p class="chisimba-status-card__detail"><strong><?php echo $e($registrationEmail ?? ''); ?></strong></p>
        <p class="chisimba-status-card__help"><?php echo $e($t('check_email_help')); ?></p>
    </section>
</main>
