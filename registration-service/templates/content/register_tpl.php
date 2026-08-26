<?php
$e = static function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$t = function ($key) { return $this->objLanguage->languageText('mod_registration_service_' . $key, 'registration-service'); };
$u = function (array $params = array()) use ($e) { return $e(html_entity_decode($this->uri($params, 'registration-service'), ENT_QUOTES, 'UTF-8')); };
$v = isset($registrationValues) && is_array($registrationValues) ? $registrationValues : array();
$abuse = isset($registrationAbuse) && is_array($registrationAbuse) ? $registrationAbuse : array();
$error = isset($registrationError) ? preg_replace('/[^a-z0-9_]/', '', (string) $registrationError) : '';
$callingCodes = isset($registrationCallingCodes) && is_array($registrationCallingCodes) ? $registrationCallingCodes : array('+27'=>'South Africa (+27)');
$usernameAvailabilityUrl = html_entity_decode($this->uri(array('action' => 'usernameavailability'), 'registration-service'), ENT_QUOTES, 'UTF-8');
?>
<main class="chisimba-workspace registration-service chisimba-form-page" aria-labelledby="registration-title">
<div class="chisimba-form-card">
<header class="chisimba-form-card__header">
    <h1 id="registration-title"><?php echo $e($t('register_title')); ?></h1>
    <p><?php echo $e($t('register_intro')); ?></p>
</header>
<?php if ($error !== ''): ?><div class="error chisimba-form-notice" role="alert"><?php echo $e($t('error_' . $error)); ?></div><?php endif; ?>
<form class="chisimba-form" method="post" action="<?php echo $u(array('action' => 'register')); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($registrationCsrf ?? ''); ?>">
<input type="hidden" name="abuse_issued_at" value="<?php echo $e($abuse['issued_at'] ?? ''); ?>">
<input type="hidden" name="abuse_nonce" value="<?php echo $e($abuse['nonce'] ?? ''); ?>">
<input type="hidden" name="abuse_signature" value="<?php echo $e($abuse['signature'] ?? ''); ?>">
<div hidden aria-hidden="true"><label for="registration-website">Website</label><input id="registration-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
<section class="chisimba-form-section chisimba-form-grid">
<div class="chisimba-form-field"><label for="registration-first-name"><?php echo $e($t('first_name')); ?></label><input id="registration-first-name" name="first_name" type="text" maxlength="50" autocomplete="given-name" value="<?php echo $e($v['firstName'] ?? ''); ?>" required></div>
<div class="chisimba-form-field"><label for="registration-surname"><?php echo $e($t('surname')); ?></label><input id="registration-surname" name="surname" type="text" maxlength="50" autocomplete="family-name" value="<?php echo $e($v['surname'] ?? ''); ?>" required></div>
<div class="chisimba-form-field chisimba-form-field--full"><label for="registration-email"><?php echo $e($t('email')); ?></label><input id="registration-email" name="email_address" type="email" maxlength="320" autocomplete="email" value="<?php echo $e($v['emailAddress'] ?? ''); ?>" required></div>
<div class="chisimba-form-field chisimba-form-field--full"><label for="registration-mobile-number"><?php echo $e($t('mobile_number')); ?></label><div class="registration-phone-input"><select id="registration-country-code" name="country_calling_code" autocomplete="tel-country-code" aria-label="<?php echo $e($t('country_calling_code')); ?>"><?php foreach($callingCodes as $code=>$label): ?><option value="<?php echo $e($code); ?>"<?php echo ($v['countryCallingCode']??'')===$code?' selected':''; ?>><?php echo $e($label); ?></option><?php endforeach; ?></select><input id="registration-mobile-number" name="mobile_number" type="tel" maxlength="32" inputmode="tel" autocomplete="tel-national" aria-describedby="registration-mobile-help" placeholder="082 123 4567" value="<?php echo $e($v['mobileNumber'] ?? ''); ?>" required></div><small id="registration-mobile-help" class="chisimba-field-help"><?php echo $e($t('mobile_number_help')); ?></small></div>
<div class="chisimba-form-field chisimba-form-field--full"><label for="registration-username"><?php echo $e($t('username')); ?></label><input id="registration-username" name="username" type="text" maxlength="255" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" autocomplete="username" aria-describedby="registration-username-help registration-username-status" value="<?php echo $e($v['username'] ?? ''); ?>" required><small id="registration-username-help" class="chisimba-field-help"><?php echo $e($t('username_help')); ?></small><div id="registration-username-status" class="registration-username-status" role="status" aria-live="polite"></div></div>
<div class="chisimba-form-field"><label for="registration-password"><?php echo $e($t('password')); ?></label><input id="registration-password" name="password" type="password" minlength="12" autocomplete="new-password" aria-describedby="registration-password-help" required><small id="registration-password-help" class="chisimba-field-help"><?php echo $e($t('password_help')); ?></small></div>
<div class="chisimba-form-field"><label for="registration-password-confirm"><?php echo $e($t('password_confirm')); ?></label><input id="registration-password-confirm" name="password_confirm" type="password" minlength="12" autocomplete="new-password" required></div>
<label class="chisimba-checkbox-field chisimba-form-field--full"><input name="accept_terms" type="checkbox" value="1" required><span><?php echo $e($t('accept_prefix')); ?> <a href="<?php echo $u(array('action' => 'terms')); ?>"><?php echo $e($t('terms_link')); ?></a>.</span></label>
</section>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $e($t('create_account')); ?></button><a class="button chisimba-button-secondary" href="<?php echo $u(array('action' => 'forgotpassword')); ?>"><?php echo $e($t('forgot_password')); ?></a></div>
</form>
<p class="chisimba-form-card__footer"><a href="<?php echo $e(html_entity_decode($this->uri(array('action' => 'showlogin'), 'security'), ENT_QUOTES, 'UTF-8')); ?>"><?php echo $e($t('already_registered')); ?></a></p>
</div>
</main>
<script type="text/javascript">
(function(){
    var username=document.getElementById('registration-username');
    var first=document.getElementById('registration-first-name');
    var surname=document.getElementById('registration-surname');
    var status=document.getElementById('registration-username-status');
    if(!username||!first||!surname||!status||!window.fetch){return;}
    var endpoint=<?php echo json_encode($usernameAvailabilityUrl, JSON_UNESCAPED_SLASHES); ?>;
    var timer=null, request=null;
    var copy={checking:<?php echo json_encode($t('username_checking')); ?>,available:<?php echo json_encode($t('username_available')); ?>,unavailable:<?php echo json_encode($t('username_unavailable')); ?>,invalid:<?php echo json_encode($t('username_invalid')); ?>,tryLabel:<?php echo json_encode($t('username_try')); ?>};
    function message(text,state){status.className='registration-username-status'+(state?' is-'+state:'');status.textContent=text;}
    function render(data){
        if(!data||data.valid===false){message(copy.invalid,'invalid');return;}
        if(data.available){message(copy.available,'available');return;}
        status.className='registration-username-status is-unavailable';status.textContent=copy.unavailable;
        if(Array.isArray(data.suggestions)&&data.suggestions.length){
            var group=document.createElement('span');group.className='registration-username-suggestions';group.appendChild(document.createTextNode(' '+copy.tryLabel+' '));
            data.suggestions.forEach(function(value){var button=document.createElement('button');button.type='button';button.className='chisimba-pill registration-username-suggestion';button.textContent=value;button.addEventListener('click',function(){username.value=value;check();username.focus();});group.appendChild(button);});
            status.appendChild(group);
        }
    }
    function check(){
        clearTimeout(timer);var value=username.value.trim().toLowerCase();
        if(!value){message('','');return;}
        if(!/^[a-z0-9][a-z0-9._-]*$/.test(value)){message(copy.invalid,'invalid');return;}
        username.value=value;message(copy.checking,'checking');
        timer=setTimeout(function(){
            if(request){request.abort();}request=new AbortController();
            var query='&username='+encodeURIComponent(value)+'&first_name='+encodeURIComponent(first.value)+'&surname='+encodeURIComponent(surname.value);
            fetch(endpoint+query,{headers:{'Accept':'application/json'},signal:request.signal,credentials:'same-origin'}).then(function(response){if(!response.ok){throw new Error('request');}return response.json();}).then(render).catch(function(error){if(error.name!=='AbortError'){message('','');}});
        },400);
    }
    username.addEventListener('input',check);first.addEventListener('change',check);surname.addEventListener('change',check);
    if(username.value){check();}
}());
</script>
