<?php
$e = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$t = function ($key) { return $this->objLanguage->languageText('mod_registration_service_' . $key, 'registration-service'); };
$action = $e(html_entity_decode($this->uri(array('action' => 'retryverification'), 'registration-service'), ENT_QUOTES, 'UTF-8'));
$error = isset($deliveryRetryError) ? preg_replace('/[^a-z0-9_]/', '', (string) $deliveryRetryError) : '';
?>
<main class="chisimba-status-page" aria-labelledby="delivery-pending-title">
    <section class="chisimba-status-card chisimba-status-card--email">
        <div class="chisimba-status-card__icon" aria-hidden="true"></div>
        <h1 id="delivery-pending-title"><?php echo $e($t('delivery_pending_title')); ?></h1>
        <p class="chisimba-status-card__lead"><?php echo $e($t('delivery_pending_intro')); ?></p>
        <p class="chisimba-status-card__detail"><?php echo $e($t('check_email_username')); ?> <strong><?php echo $e($deliveryRetryUsername ?? ''); ?></strong></p>
        <p class="chisimba-status-card__detail"><strong><?php echo $e($deliveryRetryEmail ?? ''); ?></strong></p>
        <?php if ($error !== ''): ?><div class="error chisimba-form-notice" role="alert"><?php echo $e($t('error_' . $error)); ?></div><?php endif; ?>
        <form method="post" action="<?php echo $action; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $e($deliveryRetryCsrf ?? ''); ?>">
            <button class="button" type="submit"><?php echo $e($t('delivery_pending_retry')); ?></button>
        </form>
        <p class="chisimba-status-card__help"><?php echo $e($t('delivery_pending_help')); ?></p>
    </section>
</main>
