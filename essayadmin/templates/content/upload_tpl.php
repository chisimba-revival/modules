<?php
/** Accessible Essay marking form for PHP 8.5. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
$data=$this->dbbook->getBooking("WHERE id='".addslashes($book)."'");
if(empty($data)){echo '<div class="error">This Essay submission is not available.</div>';return;}
$topicdata=$this->dbtopic->getTopic($data[0]['topicid']);
$essay=$this->dbessays->getEssay($data[0]['essayid'],'topic');
$studentname=$this->objUser->fullname($data[0]['studentid']);
$isLate=!empty($data[0]['submitdate'])&&strtotime($data[0]['submitdate'])>strtotime($topicdata[0]['closing_date']);
$download=$this->uri(array('action'=>'download','fileid'=>$data[0]['studentfileid']));
?>
<section class="chisimba-workspace">
<div class="chisimba-summary-card"><h2><?php echo $e($essay[0]['topic']??'Essay'); ?></h2><p><strong>Student:</strong> <?php echo $e($studentname); ?></p><p><strong><?php echo $isLate?'Submitted late':'Submitted'; ?>:</strong> <?php echo $e($this->objDateformat->formatDate($data[0]['submitdate'])); ?></p><p><a class="button chisimba-button-secondary" href="<?php echo $e($download); ?>"><?php echo $icons->render('download',array('decorative'=>true)); ?> Download submission</a></p></div>
<form class="chisimba-form" method="post" enctype="multipart/form-data" action="<?php echo $e($this->uri(array('action'=>'uploadsubmit'))); ?>">
<input type="hidden" name="id" value="<?php echo $e($topic); ?>"><input type="hidden" name="book" value="<?php echo $e($book); ?>">
<div class="chisimba-form-field"><label for="essay-mark">Mark (%)</label><input id="essay-mark" name="mark" type="number" min="0" max="100" step="1" value="<?php echo $e($mark); ?>" required></div>
<div class="chisimba-form-field"><label for="essay-comment">Feedback</label><textarea id="essay-comment" name="comment" rows="7"><?php echo $e($comment); ?></textarea></div>
<div class="chisimba-form-field"><label for="essay-return-file">Returned document <span class="form-help">(optional)</span></label><input id="essay-return-file" name="file" type="file" accept=".pdf,.doc,.docx,.odt,.rtf,.txt"></div>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $icons->render('save',array('decorative'=>true)); ?> Save mark</button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'marktopic','id'=>$topic))); ?>"><?php echo $icons->render('x',array('decorative'=>true)); ?> Cancel</a></div>
</form></section>
