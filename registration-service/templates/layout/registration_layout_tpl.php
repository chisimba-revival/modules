<?php
$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$t = function ($key) {
    return $this->objLanguage->languageText(
        'mod_registration_service_' . $key,
        'registration-service'
    );
};
$loginUrl = htmlspecialchars(
    html_entity_decode(
        $this->uri(array('action' => 'showlogin'), 'security'),
        ENT_QUOTES,
        'UTF-8'
    ),
    ENT_QUOTES,
    'UTF-8'
);

$aside = '<aside class="chisimba-guidance-card" aria-labelledby="account-guidance-title">'
    . '<h2 id="account-guidance-title">' . $e($t('guidance_title')) . '</h2>'
    . '<ol class="chisimba-guidance-steps">'
    . '<li><strong>' . $e($t('guidance_step_details_title')) . '</strong><span>'
    . $e($t('guidance_step_details')) . '</span></li>'
    . '<li><strong>' . $e($t('guidance_step_verify_title')) . '</strong><span>'
    . $e($t('guidance_step_verify')) . '</span></li>'
    . '<li><strong>' . $e($t('guidance_step_ready_title')) . '</strong><span>'
    . $e($t('guidance_step_ready')) . '</span></li>'
    . '</ol><p class="chisimba-guidance-card__footer"><a href="' . $loginUrl . '">'
    . $e($t('already_registered')) . '</a></p></aside>';

$layout = $this->newObject('csslayout', 'htmlelements');
$layout->setMiddleColumnContent($this->getContent());
$layout->setRightColumnContent($aside);
echo $layout->show();
?>
