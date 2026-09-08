<?php
$e=static function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
$t=function($key){return $this->objLanguage->languageText('mod_registration_service_'.$key,'registration-service');};
$record=is_array($identityRecord)?$identityRecord:array();
?>
<main class="chisimba-workspace chisimba-form-page" aria-labelledby="identity-title">
<section class="chisimba-form-card">
<header class="chisimba-form-card__header"><h1 id="identity-title"><?php echo $e($t('identity_title')); ?></h1><p><?php echo $e($t('identity_intro')); ?></p></header>
<?php if($identityMessage!==''): ?><p class="chisimba-notice chisimba-notice--success" role="status"><?php echo $e($t($identityMessage)); ?></p><?php endif; ?>
<?php if($identityError!==''): ?><p class="chisimba-notice chisimba-notice--error" role="alert"><?php echo $e($t('error_'.$identityError)); ?></p><?php endif; ?>
<form class="chisimba-form" method="post" action="<?php echo $e(html_entity_decode($this->uri(array('action'=>'saveidentity'),'registration-service'),ENT_QUOTES,'UTF-8')); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($identityCsrf); ?>">
<div class="chisimba-form-grid">
<label class="chisimba-form-field"><?php echo $e($t('identity_document_type')); ?><select name="identity_document_type" required><option value=""></option><?php foreach($identityTypes as $type): ?><option value="<?php echo $e($type); ?>"<?php echo ($record['document_type']??'')===$type?' selected':''; ?>><?php echo $e($t('identity_type_'.$type)); ?></option><?php endforeach; ?></select></label>
<label class="chisimba-form-field"><?php echo $e($t('identity_document_number')); ?><input name="identity_document_number" type="text" maxlength="128" autocomplete="off" value="<?php echo $e($record['document_number']??''); ?>" required><small><?php echo $e($t('identity_document_help')); ?></small></label>
</div><div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $e($t('identity_save')); ?></button></div>
</form></section></main>
