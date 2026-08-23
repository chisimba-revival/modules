<?php
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$t = fn($k) => $this->objLanguage->languageText('mod_registration_service_' . $k, 'registration-service');
$action = $e(html_entity_decode($this->uri(array('action' => 'resetpassword'), 'registration-service'), ENT_QUOTES, 'UTF-8'));
?>
<main class="chisimba-workspace registration-service chisimba-form-page" aria-labelledby="reset-title">
<div class="chisimba-form-card">
<header class="chisimba-form-card__header"><h1 id="reset-title"><?php echo $e($t('reset_title')); ?></h1></header>
<?php if (!empty($recoveryResetError)): ?><div class="error chisimba-form-notice" role="alert"><?php echo $e($t('error_' . $recoveryResetError)); ?></div><?php endif; ?>
<form class="chisimba-form" method="post" action="<?php echo $action; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $e($recoveryResetCsrf ?? ''); ?>">
    <input type="hidden" name="token" value="<?php echo $e($recoveryToken ?? ''); ?>">
    <section class="chisimba-form-section chisimba-form-grid">
        <div class="chisimba-form-field">
            <label for="reset-password"><?php echo $e($t('new_password')); ?></label>
            <input id="reset-password" name="password" type="password" minlength="12" autocomplete="new-password" aria-describedby="reset-password-help" required>
            <small id="reset-password-help" class="chisimba-field-help"><?php echo $e($t('password_help')); ?></small>
        </div>
        <div class="chisimba-form-field">
            <label for="reset-password-confirm"><?php echo $e($t('password_confirm')); ?></label>
            <input id="reset-password-confirm" name="password_confirm" type="password" minlength="12" autocomplete="new-password" required>
        </div>
    </section>
    <div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $e($t('reset_password')); ?></button></div>
</form>
</div>
</main>
