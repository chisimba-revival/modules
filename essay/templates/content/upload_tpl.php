<?php
/** Direct, accessible learner Essay submission form. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
$bookings=$this->dbbook->getBooking("WHERE id='".addslashes($bookId)."' AND studentid='".addslashes($this->userId)."' AND context='".addslashes($contextcode)."'");
if (empty($bookings)) { echo '<div class="error">This Essay booking is not available.</div>'; return; }
$essay=$this->dbessays->getEssay($bookings[0]['essayid'],'topic');
$essayTitle=$essay[0]['topic']??'';
$this->setVar('heading',$this->objLanguage->languageText('mod_essay_uploadessay','essay'));
?>
<section class="chisimba-workspace"><h2><?php echo $e($essayTitle); ?></h2>
<p>Upload your finished multi-page Essay document. Accepted formats are PDF, Word, OpenDocument, RTF and plain text. Submitting another file replaces the current submission until marking begins.</p>
<form class="chisimba-form" method="post" enctype="multipart/form-data" action="<?php echo $e($this->uri(array('action'=>'uploadsubmit','bookid'=>$bookId))); ?>">
<div class="chisimba-form-field"><label for="essay-file">Essay document</label><input id="essay-file" name="essayfile" type="file" required accept=".pdf,.doc,.docx,.odt,.rtf,.txt"></div>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $icons->render('upload',array('decorative'=>true)); ?> Submit essay</button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'viewallessays'))); ?>"><?php echo $icons->render('x',array('decorative'=>true)); ?> Cancel</a></div>
</form></section>
