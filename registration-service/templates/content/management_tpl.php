<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('No direct access'); }
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$t = fn($key) => $e($this->objLanguage->languageText('mod_registration_service_'.$key,'registration-service'));
$guard = $this->getObject('registrationguard','registration-service');
$icons = $this->getObject('iconservice','ui');
$clock = $this->getObject('timeanddateservice','timeanddate-service');
?>
<div class="chisimba-stack">
<section class="chisimba-form-card chisimba-form-card--wide">
<h1><?= $t('manage_title') ?></h1>
<p><?= $t('manage_intro') ?></p>
<nav class="chisimba-form-actions">
<a class="button chisimba-button-secondary" href="index.php?module=myadmin"><?= $t('manage_back') ?></a>
<a class="button chisimba-button-secondary" href="index.php?module=sysconfig&amp;action=step2&amp;pmodule_id=registration-service"><?= $t('manage_settings') ?></a>
<?= $this->getObject('contextualhelp','help')->show('registration-service','protection',true) ?>
</nav>
<?php if ($cleanupNotice): ?><p class="chisimba-notice" role="status"><?= $t('manage_'.$cleanupNotice) ?></p><?php endif; ?>
<?php if ($cleanupConfirm): ?>
<h2><?= $t('manage_confirm') ?></h2>
<?php if ($cleanupReview && !$cleanupReview['protected'] && in_array($cleanupReview['status'],array('awaiting_legal_acceptance','awaiting_verification'),true)): ?>
<p class="chisimba-wrap-anywhere"><strong><?= $e($cleanupReview['first_name'].' '.$cleanupReview['surname']) ?></strong><br><?= $e($cleanupReview['email_address']) ?></p>
<p><?= $t('manage_confirm_help') ?></p>
<form method="post" action="index.php?module=registration-service&amp;action=confirmdismiss">
<input type="hidden" name="csrf_token" value="<?= $e($cleanupCsrf) ?>">
<input type="hidden" name="id" value="<?= $e($cleanupReview['id']) ?>">
<div class="chisimba-form-actions"><button type="submit" class="button chisimba-button-danger"><?= $icons->render('trash-2',array('size'=>18,'decorative'=>true)) ?> <?= $t('manage_dismiss') ?></button><a class="button chisimba-button-secondary" href="index.php?module=registration-service&amp;action=manage"><?= $t('manage_cancel') ?></a></div>
</form>
<?php else: ?><p class="chisimba-notice"><?= $t('manage_dismiss_unavailable') ?></p><?php endif; ?>
<?php endif; ?>
<p><?= $e(sprintf($this->objLanguage->languageText('mod_registration_service_manage_policy','registration-service'),$guard->pendingDays(),$guard->retentionDays())) ?></p>
<p class="chisimba-notice"><?= $t($guard->automatic()?'manage_enabled':'manage_disabled') ?></p>
<h2><?= $t('manage_preview') ?></h2><p><?= $t('manage_scope') ?></p>
<ul><li><?= $t('manage_expire') ?>: <?= (int)$cleanupPreview['expire'] ?></li><li><?= $t('manage_redact') ?>: <?= (int)$cleanupPreview['redact'] ?></li><li><?= $t('manage_protected') ?>: <?= (int)$cleanupPreview['protected'] ?></li></ul>
<?php if (empty($cleanupPreview['rows'])): ?><p><?= $t('manage_none') ?></p><?php else: ?>
<div class="chisimba-table-wrap"><table class="chisimba-table"><thead><tr><th><?= $t('manage_name') ?></th><th><?= $t('manage_email') ?></th><th><?= $t('manage_expiry') ?></th><th><?= $t('manage_action') ?></th></tr></thead><tbody>
<?php foreach ($cleanupPreview['rows'] as $row): ?><tr><td class="chisimba-wrap-anywhere"><?= $e($row['first_name'].' '.$row['surname']) ?><?php if ($guard->suspiciousName($row['first_name'],$row['surname'])): ?><br><small><?= $t('manage_flag') ?></small><?php endif; ?></td><td class="chisimba-wrap-anywhere"><?= $e($row['email_address']) ?></td><td><?= $e($clock->formatDateTime($row['expires_at'])) ?></td><td><?= $t('manage_'.$row['operation']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</section></div>
