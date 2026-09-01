<?php
/** Learner-facing Essay submission and result overview. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
$this->setVar('heading',$this->objLanguage->code2Txt('mod_essay_listofessaysfor','essay',array('STUDENT'=>$this->user)));
?>
<section class="chisimba-workspace essay-submission-list">
<?php if(empty($data)): ?><div class="chisimba-empty-state"><p>You have not booked an Essay yet.</p><a class="button" href="<?php echo $e($this->uri(array())); ?>"><?php echo $icons->render('arrow-left',array('decorative'=>true)); ?> View Essay topics</a></div>
<?php else: foreach($data as $item):
$closed=date('Y-m-d H:i:s')>$item['date']&&$item['bypass']==='NO';
$submitted=!empty($item['studentfileid']);$marked=$item['mark']!==null&&$item['mark']!=='';
$status=$marked?'Marked':($submitted?'Submitted':($closed?'Closed':'Ready to submit'));
?>
<article class="chisimba-card essay-submission-card">
<header><div><p class="chisimba-eyebrow"><?php echo $e($item['name']); ?></p><h2><?php echo $e($item['essay']); ?></h2></div><span class="chisimba-status-badge"><?php echo $e($status); ?></span></header>
<dl class="chisimba-summary-grid"><div><dt>Closing date</dt><dd><?php echo $e($this->objDateformat->formatDate($item['date'])); ?></dd></div><?php if(!empty($item['submitdate'])): ?><div><dt>Submitted</dt><dd><?php echo $e($this->objDateformat->formatDate($item['submitdate'])); ?></dd></div><?php endif; ?><?php if($marked): ?><div><dt>Mark</dt><dd><strong><?php echo $e($item['mark']); ?>%</strong></dd></div><?php endif; ?></dl>
<?php if($marked&&!empty($item['comment'])): ?><div class="chisimba-feedback"><h3>Lecturer feedback</h3><p><?php echo nl2br($e($item['comment'])); ?></p></div><?php endif; ?>
<div class="chisimba-actions">
<?php if(!$marked&&!$closed): ?><a class="button" href="<?php echo $e($this->uri(array('action'=>'uploadessay','bookid'=>$item['id']))); ?>"><?php echo $icons->render('upload',array('decorative'=>true)); ?> <?php echo $submitted?'Replace submission':'Submit essay'; ?></a><?php endif; ?>
<?php if($marked&&!empty($item['lecturerfileid'])): ?><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'download','fileid'=>$item['lecturerfileid']))); ?>"><?php echo $icons->render('download',array('decorative'=>true)); ?> Download returned document</a><?php endif; ?>
</div></article>
<?php endforeach; endif; ?>
<div class="chisimba-actions"><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array())); ?>"><?php echo $icons->render('arrow-left',array('decorative'=>true)); ?> Essay topics</a></div>
</section>
