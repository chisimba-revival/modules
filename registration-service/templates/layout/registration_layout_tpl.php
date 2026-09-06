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
$loginUrl = rtrim((string) $this->getObject('altconfig', 'config')->getItem('KEWL_SITE_ROOT'), '/') . '/';
if (!empty($registrationReturnTo)) {
    $loginUrl .= '?return_to=' . rawurlencode((string) $registrationReturnTo);
}
$loginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
$this->appendArrayVar('headerParams','<style type="text/css">.registration-phone-input{display:grid;grid-template-columns:minmax(10rem,.9fr) minmax(0,1.4fr);gap:.75rem}.registration-phone-input select,.registration-phone-input input{box-sizing:border-box;width:100%}.registration-username-status{min-height:1.5rem;margin-top:.4rem;font-size:.92rem}.registration-username-status.is-available{color:var(--chisimba-success,#2e7d32)}.registration-username-status.is-unavailable,.registration-username-status.is-invalid{color:var(--chisimba-danger,#b42318)}.registration-username-suggestions{display:inline-flex;flex-wrap:wrap;align-items:center;gap:.35rem}.registration-username-suggestion{font:inherit;cursor:pointer}.registration-username-suggestion:hover,.registration-username-suggestion:focus{background:color-mix(in srgb,currentColor 8%,var(--chisimba-surface,#fff));text-decoration:none}.registration-purchase-summary{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin:0 0 1rem;padding:.85rem 1rem;border:1px solid var(--chisimba-border);border-left:.3rem solid var(--chisimba-primary);border-radius:var(--chisimba-radius-md,.5rem);background:var(--chisimba-info-surface,var(--chisimba-surface-muted,#f5f7fa))}.registration-purchase-summary div{display:grid;gap:.2rem}.registration-purchase-summary a{white-space:nowrap;font-weight:700}@media(max-width:38rem){.registration-phone-input{grid-template-columns:1fr}.registration-purchase-summary{align-items:flex-start;flex-direction:column}}</style>');
$this->appendArrayVar('headerParams','<style type="text/css">.registration-policy{display:grid;gap:1.25rem}.registration-policy__header{max-width:52rem}.registration-policy__header h1{margin:.15rem 0 .5rem}.registration-policy__summary,.registration-policy__introduction{font-size:1.05rem;line-height:1.65;color:var(--chisimba-text-muted,#59636e)}.registration-policy__version{font-size:.9rem;color:var(--chisimba-text-muted,#59636e)}.registration-policy__document{padding:clamp(1.25rem,3vw,2.25rem)}.registration-policy__document h2{margin-top:0}.registration-policy__section{max-width:64rem}.registration-policy__section h3{margin:1.6rem 0 .45rem}.registration-policy__section p{line-height:1.65;margin:.45rem 0}.registration-policy .chisimba-form-actions{margin-top:0}</style>');

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
$isPolicyPage = !empty($registrationPolicyPage);
if ($isPolicyPage) {
    $layout->setNumColumns(1);
} else {
    $layout->setNumColumns(3);
    $layout->layoutType = 'canvas_stacked31';
}
$layout->setMiddleColumnContent($this->getContent());
if (!$isPolicyPage) {
    $layout->setRightColumnContent($aside);
}
echo $layout->show();
?>
