<?php
$this->loadClass('link', 'htmlelements');
$siteHome = new link($siteHomeUrl);
$siteHome->link = $this->objLanguage->languageText('mod_mylearning_sitehome', 'mylearning', 'Site Home');
echo '<div class="mylearning-page"><nav class="mylearning-page__site" aria-label="Site navigation">'
    . $siteHome->show() . '</nav>' . $learningOverview . '</div>';
?>
