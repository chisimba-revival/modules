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
    rtrim((string) $this->getObject('altconfig', 'config')->getItem('KEWL_SITE_ROOT'), '/') . '/',
    ENT_QUOTES,
    'UTF-8'
);
$this->appendArrayVar('headerParams','<style type="text/css">.registration-phone-input{display:grid;grid-template-columns:minmax(10rem,.9fr) minmax(0,1.4fr);gap:.75rem}.registration-phone-input select,.registration-phone-input input{box-sizing:border-box;width:100%}.registration-username-status{min-height:1.5rem;margin-top:.4rem;font-size:.92rem}.registration-username-status.is-available{color:var(--chisimba-success,#2e7d32)}.registration-username-status.is-unavailable,.registration-username-status.is-invalid{color:var(--chisimba-danger,#b42318)}.registration-username-suggestions{display:inline-flex;flex-wrap:wrap;align-items:center;gap:.35rem}.registration-username-suggestion{font:inherit;cursor:pointer}.registration-username-suggestion:hover,.registration-username-suggestion:focus{background:color-mix(in srgb,currentColor 8%,var(--chisimba-surface,#fff));text-decoration:none}@media(max-width:38rem){.registration-phone-input{grid-template-columns:1fr}}</style>');

$guidancePrefix = isset($registrationGuidancePrefix)
    ? preg_replace('/[^a-z_]/', '', (string) $registrationGuidancePrefix)
    : 'guidance';
$aside = '<aside class="chisimba-guidance-card" aria-labelledby="account-guidance-title">'
    . '<h2 id="account-guidance-title">' . $e($t($guidancePrefix . '_title')) . '</h2>'
    . '<ol class="chisimba-guidance-steps">'
    . '<li><strong>' . $e($t($guidancePrefix . '_step_one_title')) . '</strong><span>'
    . $e($t($guidancePrefix . '_step_one')) . '</span></li>'
    . '<li><strong>' . $e($t($guidancePrefix . '_step_two_title')) . '</strong><span>'
    . $e($t($guidancePrefix . '_step_two')) . '</span></li>'
    . '<li><strong>' . $e($t($guidancePrefix . '_step_three_title')) . '</strong><span>'
    . $e($t($guidancePrefix . '_step_three')) . '</span></li>'
    . '</ol><p class="chisimba-guidance-card__footer"><a href="' . $loginUrl . '">'
    . $e($t('already_registered')) . '</a></p></aside>';

$layout = $this->newObject('csslayout', 'htmlelements');
$layout->setNumColumns(3);
$layout->layoutType = 'canvas_stacked31';
$layout->setMiddleColumnContent($this->getContent());
$layout->setRightColumnContent($aside);
echo $layout->show();
?>
