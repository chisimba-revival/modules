<?php
/** Accessible topic-area authoring form using shared skin primitives. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
$row=!empty($data[0])?$data[0]:array();
$id=(string)($row['id']??'');
$closing=(string)($row['closing_date']??date('Y-m-d H:i:s',strtotime('+7 days')));
$closingValue=$closing!==''?date('Y-m-d\TH:i',strtotime($closing)):'';
$cancel=$id!==''?$this->uri(array('action'=>'view','id'=>$id)):$this->uri(array());
?>
<form class="chisimba-form chisimba-workspace" method="post" action="<?php echo $e($this->uri(array('action'=>'savetopic'))); ?>">
<?php if($id!==''): ?><input type="hidden" name="id" value="<?php echo $e($id); ?>"><?php endif; ?>
<div class="chisimba-form-field"><label for="essay-topic-area">Topic area</label><input id="essay-topic-area" name="topicarea" value="<?php echo $e($row['name']??''); ?>" required maxlength="255"></div>
<div class="chisimba-form-field"><label for="essay-topic-description">Description</label><textarea id="essay-topic-description" name="description" rows="4"><?php echo $e($row['description']??''); ?></textarea></div>
<div class="chisimba-form-field"><label for="essay-topic-instructions"><?php echo $e($this->objLanguage->code2Txt('mod_essayadmin_instructions','essayadmin')); ?></label><textarea id="essay-topic-instructions" name="instructions" rows="5"><?php echo $e($row['instructions']??''); ?></textarea></div>
<div class="chisimba-form-field"><label for="essay-topic-closing">Closing date and time</label><input id="essay-topic-closing" type="datetime-local" name="closing_date" value="<?php echo $e($closingValue); ?>"></div>
<div class="chisimba-form-field"><label for="essay-topic-percentage">Percentage of year mark</label><input id="essay-topic-percentage" type="number" name="percentage" min="0" max="100" step="1" value="<?php echo $e($row['percentage']??0); ?>"></div>
<label><input type="checkbox" name="force"<?php echo !empty($row['forceone'])?' checked':''; ?>> Force one student per essay</label>
<label><input type="checkbox" name="bypass"<?php echo !empty($row['bypass'])?' checked':''; ?>> Bypass closing date</label>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $icons->render('save',array('decorative'=>true)); ?> Save topic area</button><a class="button chisimba-button-secondary" href="<?php echo $e($cancel); ?>"><?php echo $icons->render('x',array('decorative'=>true)); ?> Cancel</a></div>
</form>
