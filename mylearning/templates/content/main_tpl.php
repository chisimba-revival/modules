<?php
$this->loadClass('link', 'htmlelements');
$language = $this->getObject('language', 'language');
$siteHome = new link($siteHomeUrl);
$siteHome->link = $language->languageText(
    'mod_mylearning_sitehome',
    'mylearning',
    'Site Home'
);
echo '<div class="mylearning-page"><nav class="mylearning-page__site" aria-label="Site navigation">'
    . $siteHome->show() . '</nav>' . $learningOverview . '</div>';
?>
