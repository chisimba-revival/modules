<?php
/** Private generation status, usable without changing the current course. @author Derek Keats */
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($k)=>html_entity_decode($this->objLanguage->code2Txt('mod_mcqtests_input_'.$k,'mcqtests'),ENT_QUOTES|ENT_HTML5,'UTF-8');
$u=fn($p)=>html_entity_decode($this->uri($p,'mcqtests'),ENT_QUOTES,'UTF-8');
?>
<main class="chisimba-structural-main"><section class="chisimba-form-card chisimba-form-card--wide">
<h1><?php echo $e($t('heading')); ?></h1>
<div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button chisimba-button-secondary" href="<?php echo $e($u(['action'=>'quizinputjobs','contextcode'=>$inputJobContext])); ?>"><?php echo $e($t('refresh')); ?></a><?php echo $this->getObject('contextualhelp','help')->show('mcqtests','quizinput',true); ?></div>
<p><?php echo $e($t('help')); ?></p>
<?php if($inputJobError): ?><p class="error" role="alert"><?php echo $e($t('retry_error')); ?></p><?php endif; ?>
<?php if(!$inputJobs): ?><p><?php echo $e($t('empty')); ?></p><?php endif; ?>
<?php foreach($inputJobs as $job): ?>
<article class="chisimba-card"><h2><?php echo $e($job['name']); ?></h2>
<p role="status"><?php echo $e($t($job['status'])); ?></p>
<p><?php echo $e(sprintf($t('progress'),$job['progress_completed'],$job['progress_total'])); ?></p>
<?php if(in_array($job['status'],['failed','uncertain'],true)): ?>
<p><?php echo $e($t('retry_help')); ?></p>
<form method="post" action="<?php echo $e($u(['action'=>'retryquizinput','contextcode'=>$inputJobContext])); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($inputJobToken); ?>"/><input type="hidden" name="id" value="<?php echo $e($job['id']); ?>"/>
<button class="button" type="submit"><?php echo $e($t('retry')); ?></button></form>
<?php endif; ?>
<?php if(!empty($job['bankid'])): $bankTarget=$this->getObject('courseawarelaunchservice','context')->target($inputJobContext,'mcqtests',['action'=>'banks','bank'=>$job['bankid']]); ?>
<p><?php echo $e(sprintf($t('bank_summary'),$job['bank_count'],$job['draw_count']).' '.$t($job['selection'])); ?></p>
<a class="button chisimba-button-secondary" href="<?php echo $e(html_entity_decode($this->uri($bankTarget['params'],$bankTarget['module']),ENT_QUOTES,'UTF-8')); ?>"><?php echo $e($t('bank_link')); ?></a>
<?php endif; ?>
<?php if($job['name']): $target=$this->getObject('courseawarelaunchservice','context')->target($inputJobContext,'mcqtests',['action'=>'view','id'=>$job['testid']]); $review=html_entity_decode($this->uri($target['params'],$target['module']),ENT_QUOTES,'UTF-8'); ?>
<a class="button chisimba-button-secondary" href="<?php echo $e($review); ?>"><?php echo $e($t('review_link')); ?></a>
<?php endif; ?></article>
<?php endforeach; ?></section></main>
