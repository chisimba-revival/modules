<?php
/** Accessible essay authoring form using shared skin primitives. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
$row=!empty($data[0])?$data[0]:array();
?>
<form class="chisimba-form chisimba-workspace" method="post" action="<?php echo $e($this->uri(array('action'=>'saveessay'))); ?>">
<input type="hidden" name="id" value="<?php echo $e($topicid); ?>"><input type="hidden" name="essay" value="<?php echo $e($row['id']??''); ?>">
<div class="chisimba-form-field"><label for="essay-title">Essay title or question</label><input id="essay-title" name="essaytopic" value="<?php echo $e($row['topic']??''); ?>" required maxlength="255"></div>
<div class="chisimba-form-field"><label for="essay-notes">Notes or guidance</label><textarea id="essay-notes" name="notes" rows="6"><?php echo $e($row['notes']??''); ?></textarea></div>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $icons->render('save',array('decorative'=>true)); ?> Save essay</button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'view','id'=>$topicid))); ?>"><?php echo $icons->render('x',array('decorative'=>true)); ?> Cancel</a></div>
</form>
