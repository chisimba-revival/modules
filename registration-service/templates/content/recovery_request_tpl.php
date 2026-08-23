<?php
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$t = fn($k) => $this->objLanguage->languageText('mod_registration_service_' . $k, 'registration-service');
$u = fn($p = array()) => $e(html_entity_decode($this->uri($p, 'registration-service'), ENT_QUOTES, 'UTF-8'));
$abuse = $recoveryAbuse ?? array();
?>
<main class="chisimba-workspace registration-service chisimba-form-page" aria-labelledby="recovery-request-title">
<div class="chisimba-form-card">
<header class="chisimba-form-card__header">
    <h1 id="recovery-request-title"><?php echo $e($t('recovery_title')); ?></h1>
    <p><?php echo $e($t('recovery_intro')); ?></p>
</header>
<?php if (!empty($recoveryRequested)): ?>
    <div class="success chisimba-form-notice" role="status"><?php echo $e($t('recovery_requested')); ?></div>
<?php else: ?>
    <?php if (!empty($recoveryError)): ?><div class="error chisimba-form-notice" role="alert"><?php echo $e($t('error_invalid_request')); ?></div><?php endif; ?>
    <form class="chisimba-form" method="post" action="<?php echo $u(array('action' => 'requestrecovery')); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $e($recoveryRequestCsrf ?? ''); ?>">
        <input type="hidden" name="abuse_issued_at" value="<?php echo $e($abuse['issued_at'] ?? ''); ?>">
        <input type="hidden" name="abuse_nonce" value="<?php echo $e($abuse['nonce'] ?? ''); ?>">
        <input type="hidden" name="abuse_signature" value="<?php echo $e($abuse['signature'] ?? ''); ?>">
        <div hidden aria-hidden="true"><label for="recovery-website">Website</label><input id="recovery-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
        <section class="chisimba-form-section">
            <div class="chisimba-form-field">
                <label for="recovery-email"><?php echo $e($t('email')); ?></label>
                <input id="recovery-email" name="email_address" type="email" maxlength="320" autocomplete="email" required>
            </div>
        </section>
        <div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $e($t('send_recovery')); ?></button></div>
    </form>
<?php endif; ?>
</div>
</main>
