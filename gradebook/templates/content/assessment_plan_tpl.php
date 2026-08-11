<?php
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

$this->setLayoutTemplate('gradebook_layout_tpl.php');
$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('htmltable', 'htmlelements');
$this->loadClass('link', 'htmlelements');

$objHeading = new htmlheading();
$objHeading->type = 1;
$objHeading->str = $this->objLanguage->languageText('mod_gradebook_assessmentplan', 'gradebook');
echo $objHeading->show();

echo '<p>'.$this->objLanguage->languageText('mod_gradebook_assessmentplan_description', 'gradebook').'</p>';

$objProviders = $this->getObject('assessmentproviderregistry', 'gradebook');
$providers = $objProviders->all();

if (empty($providers)) {
    echo '<p>'.$this->objLanguage->languageText('mod_gradebook_noproviders', 'gradebook').'</p>';
} else {
    $objTable = new htmltable();
    $objTable->width = '100%';
    $objTable->cellspacing = 2;
    $objTable->startHeaderRow();
    $objTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_provider', 'gradebook'));
    $objTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_providercategory', 'gradebook'));
    $objTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_providerdescription', 'gradebook'));
    $objTable->endHeaderRow();

    $row = 0;
    foreach ($providers as $provider) {
        $objTable->startRow(($row++ % 2) ? 'odd' : 'even');
        $objTable->addCell(htmlspecialchars($provider['label'], ENT_QUOTES, 'UTF-8'));
        $objTable->addCell(htmlspecialchars($provider['category'], ENT_QUOTES, 'UTF-8'));
        $objTable->addCell(htmlspecialchars($provider['description'], ENT_QUOTES, 'UTF-8'));
        $objTable->endRow();
    }
    echo $objTable->show();
}

echo '<p>'.$this->objLanguage->languageText('mod_gradebook_assessmentplan_next', 'gradebook').'</p>';

$objBack = new link($this->uri(array()));
$objBack->link = $this->objLanguage->languageText('mod_gradebook_goback', 'gradebook');
echo '<p>'.$objBack->show().'</p>';
?>
