<?php
$e = static function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$t = function ($key) { return $this->objLanguage->languageText('mod_registration_service_' . $key, 'registration-service'); };
$u = function (array $params = array()) use ($e) { return $e(html_entity_decode($this->uri($params, 'registration-service'), ENT_QUOTES, 'UTF-8')); };
$v = isset($registrationValues) && is_array($registrationValues) ? $registrationValues : array();
$abuse = isset($registrationAbuse) && is_array($registrationAbuse) ? $registrationAbuse : array();
$error = isset($registrationError) ? preg_replace('/[^a-z0-9_]/', '', (string) $registrationError) : '';
?>
<main class="chisimba-workspace registration-service" aria-labelledby="registration-title">
<h1 id="registration-title"><?php echo $e($t('register_title')); ?></h1>
<p><?php echo $e($t('register_intro')); ?></p>
<?php if ($error !== ''): ?><div class="error" role="alert"><?php echo $e($t('error_' . $error)); ?></div><?php endif; ?>
<form method="post" action="<?php echo $u(array('action' => 'register')); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($registrationCsrf ?? ''); ?>">
<input type="hidden" name="abuse_issued_at" value="<?php echo $e($abuse['issued_at'] ?? ''); ?>">
<input type="hidden" name="abuse_nonce" value="<?php echo $e($abuse['nonce'] ?? ''); ?>">
<input type="hidden" name="abuse_signature" value="<?php echo $e($abuse['signature'] ?? ''); ?>">
<div hidden aria-hidden="true"><label for="registration-website">Website</label><input id="registration-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
<section class="chisimba-form-section">
<div><label for="registration-first-name"><?php echo $e($t('first_name')); ?></label><input id="registration-first-name" name="first_name" type="text" maxlength="50" autocomplete="given-name" value="<?php echo $e($v['firstName'] ?? ''); ?>" required></div>
<div><label for="registration-surname"><?php echo $e($t('surname')); ?></label><input id="registration-surname" name="surname" type="text" maxlength="50" autocomplete="family-name" value="<?php echo $e($v['surname'] ?? ''); ?>" required></div>
<div><label for="registration-email"><?php echo $e($t('email')); ?></label><input id="registration-email" name="email_address" type="email" maxlength="320" autocomplete="email" value="<?php echo $e($v['emailAddress'] ?? ''); ?>" required></div>
<div><label for="registration-username"><?php echo $e($t('username')); ?></label><input id="registration-username" name="username" type="text" maxlength="255" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" autocomplete="username" value="<?php echo $e($v['username'] ?? ''); ?>" required><small><?php echo $e($t('username_help')); ?></small></div>
<div><label for="registration-password"><?php echo $e($t('password')); ?></label><input id="registration-password" name="password" type="password" minlength="12" autocomplete="new-password" required><small><?php echo $e($t('password_help')); ?></small></div>
<div><label for="registration-password-confirm"><?php echo $e($t('password_confirm')); ?></label><input id="registration-password-confirm" name="password_confirm" type="password" minlength="12" autocomplete="new-password" required></div>
<label><input name="accept_terms" type="checkbox" value="1" required> <?php echo $e($t('accept_prefix')); ?> <a href="<?php echo $u(array('action' => 'terms')); ?>"><?php echo $e($t('terms_link')); ?></a>.</label>
</section>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $e($t('create_account')); ?></button><a class="button chisimba-button-secondary" href="<?php echo $u(array('action' => 'forgotpassword')); ?>"><?php echo $e($t('forgot_password')); ?></a></div>
</form>
<p><a href="<?php echo $e(html_entity_decode($this->uri(array('action' => 'showlogin'), 'security'), ENT_QUOTES, 'UTF-8')); ?>"><?php echo $e($t('already_registered')); ?></a></p>
</main>
