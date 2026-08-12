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

$planRows = isset($assessmentPlanRows) && is_array($assessmentPlanRows) ? $assessmentPlanRows : array();
$message = (string) $this->getParam('planmessage', '');
$error = (string) $this->getParam('planerror', '');
echo '<div class="assessment-plan-workspace">';
echo '<section class="assessment-plan-introduction">';
echo '<p>'.$this->objLanguage->languageText('mod_gradebook_assessmentplan_description', 'gradebook').'</p>';
if ($message !== '') { echo '<p class="confirm assessment-plan-notice">'.$this->objLanguage->languageText('mod_gradebook_planmessage_'.$message, 'gradebook').'</p>'; }
if ($error !== '') { echo '<p class="error assessment-plan-notice">'.$this->objLanguage->languageText('mod_gradebook_planerror_'.$error, 'gradebook').'</p>'; }
echo '<div class="assessment-plan-actions">';
if (!empty($planRows)) {
    $objSheet = new link($this->uri(array('action'=>'assessmentSheet')));
    $objSheet->link = $this->objLanguage->languageText('mod_gradebook_assessmentsheet', 'gradebook');
    echo '<span class="assessment-plan-secondary-action">'.$objSheet->show().'</span>';
}
echo '</div></section>';

echo '<section class="assessment-plan-section assessment-plan-selected">';
echo '<div class="assessment-plan-section-heading"><h2>'.$this->objLanguage->languageText('mod_gradebook_assessmentplan_currenttitle', 'gradebook').'</h2>';
echo '<p>'.$this->objLanguage->languageText('mod_gradebook_assessmentplan_currentintro', 'gradebook').'</p></div>';
if (empty($planRows)) {
    echo '<p class="assessment-plan-empty">'.$this->objLanguage->languageText('mod_gradebook_noassessmentplanitems', 'gradebook').'</p>';
} else {
    $planTable = new htmltable();
    $planTable->width = '100%';
    $planTable->cellspacing = 0;
    $planTable->cssClass = 'assessment-plan-table assessment-plan-selected-table';
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
        $status = $rowData['available'] ? $this->objLanguage->languageText('mod_gradebook_activityavailable', 'gradebook') : $this->objLanguage->languageText('mod_gradebook_activityunavailable', 'gradebook');
        $planTable->addCell('<span class="assessment-plan-status">'.$status.'</span>');
        $removeForm = '<form class="assessment-plan-remove-form" method="post" action="'.$this->uri(array('action'=>'assessmentPlanRemoveItem')).'">'
            .'<input type="hidden" name="csrf_token" value="'.htmlspecialchars($assessmentPlanCsrf, ENT_QUOTES, 'UTF-8').'">'
            .'<input type="hidden" name="item_id" value="'.htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8').'">'
            .'<button type="submit">'.$this->objLanguage->languageText('mod_gradebook_removefromplan', 'gradebook').'</button></form>';
        $planTable->addCell($removeForm);
        $planTable->endRow();
    }
    echo $planTable->show();
}
echo '</section>';

$objProviders = $this->getObject('assessmentproviderregistry', 'gradebook');
$providers = $objProviders->all();
echo '<section class="assessment-plan-section assessment-plan-providers">';
echo '<div class="assessment-plan-section-heading"><h2>'.$this->objLanguage->languageText('mod_gradebook_assessmentplan_providerstitle', 'gradebook').'</h2>';
echo '<p>'.$this->objLanguage->languageText('mod_gradebook_assessmentplan_providersintro', 'gradebook').'</p></div>';
if (empty($providers)) {
    echo '<p class="assessment-plan-empty">'.$this->objLanguage->languageText('mod_gradebook_noproviders', 'gradebook').'</p>';
} else {
    $objTable = new htmltable();
    $objTable->width = '100%';
    $objTable->cellspacing = 0;
    $objTable->cssClass = 'assessment-plan-table assessment-plan-provider-table';
    $objTable->startHeaderRow();
    $objTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_provider', 'gradebook'));
    $objTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_providercategory', 'gradebook'));
    $objTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_providerdescription', 'gradebook'));
    $objTable->addHeaderCell($this->objLanguage->languageText('mod_gradebook_action', 'gradebook'));
    $objTable->endHeaderRow();
    $row = 0;
    foreach ($providers as $provider) {
        $objTable->startRow(($row++ % 2) ? 'odd' : 'even');
        $objTable->addCell(htmlspecialchars($provider['label'], ENT_QUOTES, 'UTF-8'));
        $objTable->addCell(htmlspecialchars($provider['category'], ENT_QUOTES, 'UTF-8'));
        $objTable->addCell(htmlspecialchars($provider['description'], ENT_QUOTES, 'UTF-8'));
        $providerAction = '';
        $adapter = $objProviders->adapter($provider['key']);
        $selectable = in_array('activity_selection', (array) $provider['capabilities'], true)
            && is_object($adapter)
            && is_callable(array($adapter, 'listActivities'))
            && is_callable(array($adapter, 'getActivity'));
        if ($selectable) {
            $objProviderAdd = new link($this->uri(array('action'=>'assessmentPlanAddProvider', 'provider_key'=>$provider['key'])));
            $objProviderAdd->link = $this->objLanguage->languageText('mod_gradebook_addproviderassessment', 'gradebook');
            $providerAction = '<span class="assessment-plan-provider-action">'.$objProviderAdd->show().'</span>';
        }
        $objTable->addCell($providerAction);
        $objTable->endRow();
    }
    echo $objTable->show();
}
echo '</section>';

echo '<section class="assessment-plan-next-step"><div><h2>'.$this->objLanguage->languageText('mod_gradebook_assessmentplan_nexttitle', 'gradebook').'</h2>';
echo '<p>'.$this->objLanguage->languageText('mod_gradebook_assessmentplan_next', 'gradebook').'</p></div>';
if (!empty($planRows)) {
    $objSheet = new link($this->uri(array('action'=>'assessmentSheet')));
    $objSheet->link = $this->objLanguage->languageText('mod_gradebook_assessmentsheet', 'gradebook');
    echo '<span class="assessment-plan-next-step-action">'.$objSheet->show().'</span>';
}
echo '</section>';

$objBack = new link($this->uri(array()));
$objBack->link = $this->objLanguage->languageText('mod_gradebook_goback', 'gradebook');
echo '<p class="assessment-plan-back">'.$objBack->show().'</p>';
echo '</div>';
?>
