<?php
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = fn($key) => $this->objLanguage->languageText('mod_registration_service_'.$key, 'registration-service');
?>
<main class="chisimba-workspace registration-service" aria-labelledby="registration-disabled-title">
    <h1 id="registration-disabled-title"><?php echo $e($t('registration_disabled_title')); ?></h1>
    <p role="status"><?php echo $e($t('registration_disabled')); ?></p>
    <div class="chisimba-form-actions">
        <a class="button" href="<?php echo $e(rtrim((string) $this->getObject('altconfig', 'config')->getItem('KEWL_SITE_ROOT'), '/').'/'); ?>"><?php echo $e($t('sign_in')); ?></a>
    </div>
</main>
