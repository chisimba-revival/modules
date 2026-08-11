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

$planStore = $this->getObject('dbgradebookassessmentplans', 'gradebook');
$itemStore = $this->getObject('dbgradebookassessmentplanitems', 'gradebook');
$plan = $planStore->findForContext($this->contextCode);
$planItems = $plan ? $itemStore->getForPlan($plan['id']) : array();
$message = (string) $this->getParam('planmessage', '');
$error = (string) $this->getParam('planerror', '');
if ($message !== '') { echo '<p class="confirm">'.$this->objLanguage->languageText('mod_gradebook_planmessage_'.$message, 'gradebook').'</p>'; }
if ($error !== '') { echo '<p class="error">'.$this->objLanguage->languageText('mod_gradebook_planerror_'.$error, 'gradebook').'</p>'; }

$objAdd = new link($this->uri(array('action'=>'assessmentPlanAddMcq')));
$objAdd->link = $this->objLanguage->languageText('mod_gradebook_addmcqassessment', 'gradebook');
echo '<p>'.$objAdd->show().'</p>';

if (!empty($planItems)) {
    $planTable = new htmltable(); $planTable->width = '100%'; $planTable->cellspacing = 2;
    $planTable->startHeaderRow();
    $planTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_assessment', 'gradebook'));
    $planTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_provider', 'gradebook'));
    $planTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_planitemweight', 'gradebook'));
    $planTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_planstatus', 'gradebook'));
    $planTable->endHeaderRow();
    $row = 0;
    foreach ($planItems as $item) {
        $planTable->startRow(($row++ % 2) ? 'odd' : 'even');
        $planTable->addCell(htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'));
        $planTable->addCell(htmlspecialchars($item['provider_module'], ENT_QUOTES, 'UTF-8'));
        $planTable->addCell(htmlspecialchars($item['weight'], ENT_QUOTES, 'UTF-8').'%');
        $planTable->addCell(htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8'));
        $planTable->endRow();
    }
    echo $planTable->show();
}

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
