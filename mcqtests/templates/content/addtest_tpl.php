<?php
/**
 * Modern test add/edit form.
 *
 * @category  Chisimba
 * @package   mcqtests
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
$this->loadClass('form', 'htmlelements');
$this->loadClass('textinput', 'htmlelements');
$this->loadClass('textarea', 'htmlelements');
$this->loadClass('radio', 'htmlelements');
$this->loadClass('checkbox', 'htmlelements');
$this->loadClass('dropdown', 'htmlelements');
$this->loadClass('link', 'htmlelements');
$objPopupcal = $this->newObject('datepickajax', 'popupcalendar');
$objIconService = $this->getObject('iconservice', 'ui');
$lang = function ($key, $module = 'mcqtests') { return $this->objLanguage->languageText($key, $module); };
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$addHeading=$lang('mod_mcqtests_addtest'); $editHeading=$lang('mod_mcqtests_edittest');
$nameLabel=$lang('mod_mcqtests_wordname'); $statusLabel=$lang('mod_mcqtests_status');
$notactiveLabel=$lang('mod_mcqtests_inactive'); $openLabel=$lang('mod_mcqtests_openforentry');
$startLabel=$lang('mod_mcqtests_startdate'); $closeLabel=$lang('mod_mcqtests_closingdate');
$descriptionLabel=$lang('mod_mcqtests_description'); $saveLabel=$this->objLanguage->languageText('word_save');
$exitLabel=$this->objLanguage->languageText('word_cancel'); $setTimedLabel=$lang('mod_mcqtests_settimed');
$setDurationLabel=$lang('mod_mcqtests_setduration'); $hourLabel=$lang('mod_mcqtests_hours'); $minLabel=$lang('mod_mcqtests_minutes');
$assessmentSheetLabel=$lang('mod_mcqtests_assessmentsheet'); $assessmentSheetNote=$lang('mod_mcqtests_assessmentsheet_note');
$testTypeLabel=$lang('mod_mcqtests_testtype'); $formativeLabel=$this->objLanguage->languageText('word_formative');
$summativeLabel=$this->objLanguage->languageText('word_summative'); $advancedLabel=$lang('mod_mcqtest_word_advanced');
$qSequenceLabel=$lang('mod_mcqtests_questionorder'); $aSequenceLabel=$lang('mod_mcqtests_answerorder');
$scrambledLabel=$this->objLanguage->languageText('word_scrambled'); $sequentialLabel=$this->objLanguage->languageText('word_sequential');
$restrictLabel=$lang('mod_mcqtests_restrict'); $anyLabLabel=$lang('mod_mcqtests_labs'); $addLabLabel=$lang('mod_mcqtests_addlab');
$errName=$lang('mod_mcqtests_entername'); $permissionsLabel=$lang('mod_mcqtests_coursepermissionslabel');
$coursePermissionPrivate=$lang('mod_mcqtests_privatecourse'); $coursePermissionPublic=$lang('mod_mcqtests_publiccourse');
$detailsHeading=$lang('mod_mcqtests_testdetailsLabel');
$this->setVar('heading', $mode==='edit' ? $editHeading : $addHeading);
if (!empty($data)) {
    $row=$data[0]; $id=$row['id']; $name=$row['name']; $status=$row['status'];
    $startLocal=$this->objDate->inTimezone($row['startdate']); $closeLocal=$this->objDate->inTimezone($row['closingdate']);
    $start=$startLocal===null?'':$startLocal->format('Y-m-d H:i'); $close=$closeLocal===null?'':$closeLocal->format('Y-m-d H:i');
    $timed=$row['timed']; $duration=(int)$row['duration']; $hour=$duration>0?floor($duration/60):0; $min=$duration>0?$duration%60:0;
    $testType=$row['testtype']; $qSequence=$row['qsequence']; $aSequence=$row['asequence']; $comLab=$row['comlab'];
    $description=$row['description']; $coursePermissions=$row['coursepermissions'];
} else {
    $today=$this->objDate->inTimezone($this->objDate->nowStorage())->format('Y-m-d H:i');
    $id=''; $name=''; $status='inactive'; $start=$today; $close=$today; $timed=''; $hour=0; $min=0;
    $testType='Formative'; $qSequence='Sequential'; $aSequence='Sequential'; $comLab=''; $description=''; $coursePermissions='Private';
}
$nameInput=new textinput('name',$name); $nameInput->cssClass='chisimba-input';
if ($mode==='edit') {
    $statusRadio=new radio('status'); $statusRadio->addOption('inactive',$notactiveLabel); $statusRadio->addOption('open',$openLabel);
    $statusRadio->setSelected($status); $statusRadio->setBreakSpace(''); $statusShow=$statusRadio->show();
} else { $statusShow='<strong>'.$esc($notactiveLabel).'</strong><input type="hidden" name="status" value="inactive" />'; }
$assessmentSheetLink=new link($this->uri(array('action'=>'assessmentSheet'),'gradebook')); $assessmentSheetLink->link=$assessmentSheetLabel;
$startField=$objPopupcal->show('start','yes','no',$start); $closeField=$objPopupcal->show('close','yes','no',$close);
$check=!empty($timed); $timedCheck=new checkbox('timed','',$check);
$timedCheck->extra=' onchange="var h=document.getElementById(\'input_hour\'),m=document.getElementById(\'input_min\');h.disabled=m.disabled=!this.checked;if(!this.checked){h.value=\'0\';m.value=\'0\';}"';
$hourDrop=new dropdown('hour'); for($x=0;$x<=23;$x++){$hourDrop->addOption($x,$x);} $hourDrop->setSelected($hour); if(!$check){$hourDrop->extra=' disabled="true"';}
$minDrop=new dropdown('min'); for($x=0;$x<=59;$x++){$minDrop->addOption($x,$x);} $minDrop->setSelected($min); if(!$check){$minDrop->extra=' disabled="true"';}
$testTypeRadio=new radio('testType'); $testTypeRadio->addOption($formativeLabel,$formativeLabel); $testTypeRadio->addOption($summativeLabel,$summativeLabel); $testTypeRadio->addOption($advancedLabel,$advancedLabel); $testTypeRadio->setSelected(!empty($testType)?$testType:'Formative'); $testTypeRadio->setBreakSpace('');
$qRadio=new radio('qSequence'); $qRadio->addOption($sequentialLabel,$sequentialLabel); $qRadio->addOption($scrambledLabel,$scrambledLabel); $qRadio->setSelected(!empty($qSequence)?$qSequence:'Sequential'); $qRadio->setBreakSpace('');
$aRadio=new radio('aSequence'); $aRadio->addOption($sequentialLabel,$sequentialLabel); $aRadio->addOption($scrambledLabel,$scrambledLabel); $aRadio->setSelected(!empty($aSequence)?$aSequence:'Sequential'); $aRadio->setBreakSpace('');
$permissionsRadio=new radio('coursePermissions'); $permissionsRadio->addOption('Private',$coursePermissionPrivate); $permissionsRadio->addOption('Public',$coursePermissionPublic); $permissionsRadio->setSelected(!empty($coursePermissions)?$coursePermissions:'Private'); $permissionsRadio->setBreakSpace('');
$labDrop=new dropdown('comLab'); $labDrop->addOption(NULL,$anyLabLabel); foreach($this->arrComLabs as $lab){$labDrop->addOption($lab,$lab);} if(!empty($comLab)){$labDrop->setSelected($comLab);}
$labLink=new link($this->uri(array('action'=>'addlab','id'=>$id,'mode'=>$mode))); $labLink->link=$addLabLabel;
$descriptionInput=new textarea('description',$description,7,67);
$hidden=$mode==='edit'?'<input type="hidden" name="id" value="'.$esc($id).'" />':'';
$formAction=$this->getParam('action')==='edit2'?$this->uri(array('action'=>'applyaddtest','prevaction'=>'edit2')):$this->uri(array('action'=>'applyaddtest'));
$form=new form('savetest',$formAction); $form->addRule('name',$errName,'required');
$section=function($title,$body){return '<section class="chisimba-form-section mcq-form-section"><h2 class="mcq-form-section-title">'.$title.'</h2><div class="mcq-form-section-body">'.$body.'</div></section>';};
$field=function($label,$control,$class=''){return '<div class="chisimba-form-field '.$class.'"><label>'.$label.'</label>'.$control.'</div>';};
$choice=function($label,$control){return '<fieldset class="chisimba-form-field chisimba-choice-field"><legend>'.$label.'</legend><div class="chisimba-choice-group">'.$control.'</div></fieldset>';};
$basic='<div class="mcq-form-grid">'.$field($esc($nameLabel),$nameInput->show()).$choice($esc($statusLabel),$statusShow).'</div>'
    .'<div class="chisimba-form-field mcq-assessment-sheet-field"><strong>'.$esc($assessmentSheetLabel).'</strong><p>'.$esc($assessmentSheetNote).' '.$assessmentSheetLink->show().'</p></div>';
$schedule='<div class="mcq-form-grid">'.$field($esc($startLabel),$startField).$field($esc($closeLabel),$closeField)
    .$field($esc($setTimedLabel),'<div class="mcq-inline-choice">'.$timedCheck->show().'</div>')
    .$field($esc($setDurationLabel),'<div class="mcq-duration">'.$hourDrop->show().' <span>'.$esc($hourLabel).'</span> '.$minDrop->show().' <span>'.$esc($minLabel).'</span></div>').'</div>';
$settings=$choice($esc($testTypeLabel),$testTypeRadio->show()).$choice($esc($qSequenceLabel),$qRadio->show()).$choice($esc($aSequenceLabel),$aRadio->show()).$choice($esc($permissionsLabel),$permissionsRadio->show());
$access=$field($esc($restrictLabel),'<div class="mcq-lab-control">'.$labDrop->show().' '.$labLink->show().'</div>');
$descriptionSection=$field($esc($descriptionLabel),$descriptionInput->show());
$saveIcon=$objIconService->render('circle-check', array('decorative'=>true, 'class'=>'chisimba-action-icon'));
$cancelIcon=$objIconService->render('x', array('decorative'=>true, 'class'=>'chisimba-action-icon'));
$content='<div class="chisimba-workspace mcq-test-editor"><div class="chisimba-form">'
    .$section($esc($detailsHeading),$basic)
    .$section($esc($startLabel),$schedule)
    .$section($esc($testTypeLabel),$settings)
    .$section($esc($restrictLabel),$access)
    .$section($esc($descriptionLabel),$descriptionSection)
    .$hidden.'<div class="chisimba-form-actions"><button class="button" type="submit" name="save" value="'.$esc($saveLabel).'">'.$saveIcon.'<span>'.$esc($saveLabel).'</span></button>'
    .'<button class="button chisimba-button-secondary" type="button" onclick="document.getElementById(\'form_exit\').submit()">'.$cancelIcon.'<span>'.$esc($exitLabel).'</span></button></div></div></div>';
$form->addToForm($content); echo $form->show();
$exitAction=$this->getParam('action')==='edit2'?$this->uri(array('action'=>'applyaddtest','prevaction'=>'edit2')):$this->uri(array('action'=>'applyaddtest'));
$exitForm=new form('exit',$exitAction); $exitForm->addToForm($hidden.'<input type="hidden" name="save" value="'.$esc($exitLabel).'" />'); echo $exitForm->show();
?>
