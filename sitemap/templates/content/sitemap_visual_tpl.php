<?php

$this->loadClass('htmlheading', 'htmlelements');

$showModule = $this->getParam('showmodule', 'all');

if (!in_array($showModule, $modules)) {
    $showModule = 'all';
}

$mapUrl = $this->uri(array('action'=>'visualmap', 'showmodule'=>$showModule));

if ($showModule == 'all') {
    
    $htmlHeading = new htmlheading();
    $htmlHeading->type = 1;
    $htmlHeading->str = $this->objLanguage->languageText('mod_sitemap_allmodules', 'sitemap');
    echo $htmlHeading->show();
} else {
    
    $htmlHeading = new htmlheading();
    $htmlHeading->type = 1;
    $htmlHeading->str = $this->objLanguage->languageText('phrase_sitemap', 'sitemap').' - '.$this->objLanguage->languageText('mod_'.$showModule.'_name', $showModule);
    echo $htmlHeading->show();
}

echo $this->generateDropdownNavigation($modules, $showModule, 'visual');

echo $this->getObject('nativeplayer', 'files')->download($mapUrl, false);

?>