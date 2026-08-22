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
