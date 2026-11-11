<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); $t=fn($k)=>$this->text($k);
$url=fn($p)=>html_entity_decode($this->uri($p,'sightsandsounds'),ENT_QUOTES,'UTF-8');
$state=json_decode($ssRun['state'],true,512,JSON_THROW_ON_ERROR); $ids=array_keys($state);
$selected=$this->getParam('itemid',''); if (!is_string($selected) || !isset($state[$selected])) $selected=$ids[0];
$position=array_search($selected,$ids,true); $entry=$state[$selected];
$item=$this->bank->item($ssScope,$selected); $activity=$this->bank->activity($ssScope,$ssRun['activityid']);
$receipt=$this->getObject('practicecompletions','sightsandsounds')->forRun($ssRun['id']);
$rulesService=$this->getObject('assignmentrules','sightsandsounds');$rules=$rulesService->forActivity($ssScope,$activity['id']);$graded=$rules['mode']==='formative';
$correct=count(array_filter($state,fn($s)=>$s['correct']));
?>
<div data-ss-player data-error="<?= $e($t('error_failed')) ?>"><h1><?= $e($activity['title']) ?></h1><p><?= $e($t($rules['mode'])) ?><?php if(!$graded||$receipt): ?> · <?= $e($t('progress')) ?> <?= $correct ?>/<?= count($ids) ?> (<?= round(100*$correct/count($ids)) ?>%)<?php endif ?></p>
<div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button" href="<?= $e($url(['action'=>'home'])) ?>"><?= $e($t('activities')) ?></a>
<?= $this->getObject('contextualhelp','help')->show('sightsandsounds','practice') ?></div>
<?php if ($this->getVar('ssError')): ?><p role="alert"><?= $e($this->getVar('ssError')) ?></p><?php endif ?>
<?php if ($correct===count($ids)): ?><p role="status"><?= $e($t('mastered')) ?></p><?php endif ?>
<?php if ($receipt): ?><section class="chisimba-form-card"><h2><?= $e($t($graded?'assessment_completed':'completed')) ?></h2><p><?= (int)$receipt['correct'] ?>/<?= (int)$receipt['total'] ?> · <?= round(100*$receipt['correct']/$receipt['total']) ?>%</p><p><?= $e($t($graded?'formative_receipt':'receipt_note')) ?></p><?php if($rulesService->late($ssScope,$activity['id'],$receipt['createdat'])): ?><p><?= $e($t('late')) ?></p><?php endif ?></section><?php else: ?>
<section class="chisimba-form-card chisimba-form-card--wide">
<h2><?= $e($t('question')) ?> <?= $position+1 ?>/<?= count($ids) ?></h2>
<?php if ($item['kind']==='image'): ?>
<a href="<?= $e($url(['action'=>'media','id'=>$selected])) ?>" target="_blank" rel="noopener" aria-label="<?= $e($t('open_image')) ?>"><img src="<?= $e($url(['action'=>'media','id'=>$selected])) ?>" alt="<?= $e($t('identify_image')) ?>" width="640"></a>
<?php else: ?><span class="chisimba-content-block"><audio controls preload="metadata" aria-label="<?= $e($t('identify_audio')) ?>" src="<?= $e($url(['action'=>'media','id'=>$selected])) ?>"></audio></span><?php endif ?>
<form method="post" action="<?= $e($url(['action'=>'check'])) ?>" data-ss-answer data-ss-confirm="<?= $graded?$e($t('submit_confirm')):'' ?>">
<?= $this->access->tokenField() ?><input type="hidden" name="id" value="<?= $e($ssRun['id']) ?>"><input type="hidden" name="itemid" value="<?= $e($selected) ?>"><input type="hidden" name="revision" value="<?= (int)$ssRun['revision'] ?>">
<div class="chisimba-form-field"><label for="ss-answer"><?= $e($t('identify')) ?></label><input id="ss-answer" name="answer" type="text" maxlength="160" autocomplete="off" required value="<?= $e($this->getVar('ssDraft',$entry['answer'])) ?>"></div>
<div class="chisimba-form-actions"><button type="submit"><?= $e($t($graded?'save_answer':'check')) ?></button><?php if($graded): ?><button type="submit" formaction="<?= $e($url(['action'=>'finish'])) ?>" formnovalidate><?= $e($t('submit_assignment')) ?></button><?php endif ?></div>
</form>
<?php if($graded&&$entry['checks']>0): ?><p role="status"><?= $e($t('answer_saved')) ?></p><?php endif ?>
<?php if (!$graded && $entry['checks']>0): ?><div role="status"><p><strong><?= $e($t($entry['correct']?'correct':'try_again')) ?></strong> <?= $e($item['answer']) ?></p><p><?= nl2br($e($item['feedback'])) ?></p><p><?= $e($t('checked_saved')) ?></p></div><?php endif ?>
<p><?= $e($item['credit']) ?></p>
<div class="chisimba-form-actions">
<?php if ($position>0): ?><a class="button" href="<?= $e($url(['action'=>'play','id'=>$ssRun['id'],'itemid'=>$ids[$position-1]])) ?>"><?= $e($t('previous')) ?></a><?php endif ?>
<?php if ($position<count($ids)-1): ?><a class="button" href="<?= $e($url(['action'=>'play','id'=>$ssRun['id'],'itemid'=>$ids[$position+1]])) ?>"><?= $e($t('next')) ?></a><?php endif ?>
</div></section>
<details><summary><?= $e($t('review_questions')) ?></summary><nav aria-label="<?= $e($t('questions')) ?>"><ol>
<?php foreach ($ids as $n=>$qid): ?><li><a href="<?= $e($url(['action'=>'play','id'=>$ssRun['id'],'itemid'=>$qid])) ?>" <?= $qid===$selected?'aria-current="step"':'' ?>><?= $e($t('question')) ?> <?= $n+1 ?> — <?= $e($t($graded?($state[$qid]['checks']?'answer_saved':'unanswered'):($state[$qid]['correct']?'correct':($state[$qid]['checks']?'retry':'unanswered')))) ?></a></li><?php endforeach ?>
</ol></nav></details>
<?php if(!$graded): ?><form method="post" action="<?= $e($url(['action'=>'finish'])) ?>" class="chisimba-form-actions">
<?= $this->access->tokenField() ?><input type="hidden" name="id" value="<?= $e($ssRun['id']) ?>"><input type="hidden" name="revision" value="<?= (int)$ssRun['revision'] ?>">
<button type="submit"><?= $e($t('finish')) ?></button><span><?= $e($t('finish_note')) ?></span>
</form><?php endif; endif ?>
<?php if(!$graded): ?><details><summary><?= $e($t('new_set')) ?></summary><form method="post" action="<?= $e($url(['action'=>'start'])) ?>" class="chisimba-form-card">
<?= $this->access->tokenField() ?><input type="hidden" name="id" value="<?= $e($activity['id']) ?>"><input type="hidden" name="fresh" value="1">
<p><?= $e($t('new_set_note')) ?></p><label><input type="checkbox" required> <?= $e($t('new_set_confirm')) ?></label>
<div class="chisimba-form-actions"><button type="submit"><?= $e($t('new_set')) ?></button></div>
</form>
</details><?php else: ?><p><a class="button" href="<?= $e($url(['action'=>'activity','id'=>$activity['id']])) ?>"><?= $e($t('open_activity')) ?></a></p><?php endif ?></div>
