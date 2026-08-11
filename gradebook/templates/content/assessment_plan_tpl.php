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

$planRows = isset($assessmentPlanRows) && is_array($assessmentPlanRows) ? $assessmentPlanRows : array();
$message = (string) $this->getParam('planmessage', '');
$error = (string) $this->getParam('planerror', '');
if ($message !== '') { echo '<p class="confirm">'.$this->objLanguage->languageText('mod_gradebook_planmessage_'.$message, 'gradebook').'</p>'; }
if ($error !== '') { echo '<p class="error">'.$this->objLanguage->languageText('mod_gradebook_planerror_'.$error, 'gradebook').'</p>'; }

$objAdd = new link($this->uri(array('action'=>'assessmentPlanAddMcq')));
$objAdd->link = $this->objLanguage->languageText('mod_gradebook_addmcqassessment', 'gradebook');
echo '<p>'.$objAdd->show();
if (!empty($planRows)) {
    $objSheet = new link($this->uri(array('action'=>'assessmentSheet')));
    $objSheet->link = $this->objLanguage->languageText('mod_gradebook_assessmentsheet', 'gradebook');
    echo ' | '.$objSheet->show();
}
echo '</p>';

if (!empty($planRows)) {
    $planTable = new htmltable(); $planTable->width = '100%'; $planTable->cellspacing = 2;
    $planTable->startHeaderRow();
    $planTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_assessment', 'gradebook'));
    $planTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_provider', 'gradebook'));
    $planTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_activitystatus', 'gradebook'));
    $planTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_action', 'gradebook'));
    $planTable->endHeaderRow();
    $row = 0;
    foreach ($planRows as $rowData) {
        $item = $rowData['item'];
        $planTable->startRow(($row++ % 2) ? 'odd' : 'even');
        $planTable->addCell(htmlspecialchars($rowData['title'], ENT_QUOTES, 'UTF-8'));
        $planTable->addCell(htmlspecialchars($rowData['provider'], ENT_QUOTES, 'UTF-8'));
        $planTable->addCell($rowData['available'] ? $this->objLanguage->languageText('mod_gradebook_activityavailable', 'gradebook') : $this->objLanguage->languageText('mod_gradebook_activityunavailable', 'gradebook'));
        $removeForm = '<form method="post" action="'.$this->uri(array('action'=>'assessmentPlanRemoveItem')).'">'
            .'<input type="hidden" name="csrf_token" value="'.htmlspecialchars($assessmentPlanCsrf, ENT_QUOTES, 'UTF-8').'">'
            .'<input type="hidden" name="item_id" value="'.htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8').'">'
            .'<button type="submit">'.$this->objLanguage->languageText('mod_gradebook_removefromplan', 'gradebook').'</button></form>';
        $planTable->addCell($removeForm);
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
