<?php
/** Accessible topic-area authoring form using shared skin primitives. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
$row=!empty($data[0])?$data[0]:array();
$id=(string)($row['id']??'');
$defaultClosing=$this->objTimeAndDate->nowUtc()->modify('+7 days');
$closing=(string)($row['closing_date']??$this->objTimeAndDate->toStorage($defaultClosing));
$closingLocal=$this->objTimeAndDate->inTimezone($closing);
if($closingLocal===null){$closingLocal=new DateTimeImmutable('+7 days',new DateTimeZone($this->objTimeAndDate->siteTimezone()));}
$dateTimePicker=$this->getObject('datetimepicker','htmlelements');
$dateTimePicker->setName('closing');
$dateTimePicker->setValue($closingLocal);
$dateTimePicker->setLabels('Date','Time');
$cancel=$id!==''?$this->uri(array('action'=>'view','id'=>$id)):$this->uri(array());
?>
<form class="chisimba-form chisimba-workspace" method="post" action="<?php echo $e($this->uri(array('action'=>'savetopic'))); ?>">
<?php if($id!==''): ?><input type="hidden" name="id" value="<?php echo $e($id); ?>"><?php endif; ?>
<div class="chisimba-form-field"><label for="essay-topic-area">Topic area</label><input id="essay-topic-area" name="topicarea" value="<?php echo $e($row['name']??''); ?>" required maxlength="255"></div>
<div class="chisimba-form-field"><label for="essay-topic-description">Description</label><textarea id="essay-topic-description" name="description" rows="4"><?php echo $e($row['description']??''); ?></textarea></div>
<div class="chisimba-form-field"><label for="essay-topic-instructions"><?php echo $e($this->objLanguage->code2Txt('mod_essayadmin_instructions','essayadmin')); ?></label><textarea id="essay-topic-instructions" name="instructions" rows="5"><?php echo $e($row['instructions']??''); ?></textarea></div>
<fieldset class="chisimba-form-field"><legend>Closing date and time</legend><?php echo $dateTimePicker->show(); ?></fieldset>
<label><input type="checkbox" name="force"<?php echo !empty($row['forceone'])?' checked':''; ?>> Force one student per essay</label>
<label><input type="checkbox" name="bypass"<?php echo !empty($row['bypass'])?' checked':''; ?>> Bypass closing date</label>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $icons->render('save',array('decorative'=>true)); ?> Save topic area</button><a class="button chisimba-button-secondary" href="<?php echo $e($cancel); ?>"><?php echo $icons->render('x',array('decorative'=>true)); ?> Cancel</a></div>
</form>
