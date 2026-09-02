<?php
/** Accessible Essay marking form for PHP 8.5. @package essay @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
$data=$this->dbbook->getBooking("WHERE id='".addslashes($book)."'");
if(empty($data)){echo '<div class="error">This Essay submission is not available.</div>';return;}
$topicdata=$this->dbtopic->getTopic($data[0]['topicid']);
$essay=$this->dbessays->getEssay($data[0]['essayid'],'topic');
$studentname=$this->objUser->fullname($data[0]['studentid']);
$isLate=!empty($data[0]['submitdate'])&&$data[0]['submitdate']>$topicdata[0]['closing_date'];
$written=trim((string)($data[0]['submission_html']??''))!=='';
$download=$this->uri(array('action'=>'download','fileid'=>$data[0]['studentfileid']??''));
$displayMark=($mark==='0'&&$data[0]['mark']!==null&&$data[0]['mark']!=='')?$data[0]['mark']:$mark;
$displayComment=($comment===''&&!empty($data[0]['comment']))?$data[0]['comment']:$comment;
$suggestion=is_array($aiSuggestion??null)?$aiSuggestion:array();
$review=(array)($suggestion['authorshipReview']??array());
$adjustment=(int)($data[0]['integrity_adjustment']??0);
$adjustmentReason=(string)($data[0]['integrity_reason']??'');
$suggestedMark=max(0,min(100,(int)($suggestion['mark']??0)));
if (!empty($suggestion) && $displayComment==='') { $displayComment=(string)($suggestion['feedback']??''); }
$initialFinal=!empty($suggestion)?max(0,min(100,$suggestedMark+$adjustment)):max(0,min(100,(int)$displayMark));
?>
<section class="chisimba-workspace">
<div class="chisimba-summary-card"><h2><?php echo $e($essay[0]['topic']??'Essay'); ?></h2><p><strong>Student:</strong> <?php echo $e($studentname); ?></p><p><strong><?php echo $isLate?'Submitted late':'Submitted'; ?>:</strong> <?php echo $e($this->objTimeAndDate->formatDateTime($data[0]['submitdate'])); ?></p><?php if(!$written): ?><p><a class="button chisimba-button-secondary" href="<?php echo $e($download); ?>"><?php echo $icons->render('download',array('decorative'=>true)); ?> Download submission</a></p><?php endif; ?></div>
<?php if($written): ?><article class="chisimba-longform-reading"><h3>Submitted essay</h3><div class="chisimba-prose"><?php echo $data[0]['submission_html']; ?></div></article><?php endif; ?>
<?php if($aiMarkingAvailable && empty($suggestion)): ?>
<form method="post" action="<?php echo $e($this->uri(array('action'=>'aiassistmark'))); ?>" class="chisimba-form-actions">
<input type="hidden" name="book" value="<?php echo $e($book); ?>"><input type="hidden" name="csrf_token" value="<?php echo $e($aiMarkingToken); ?>">
<button class="button chisimba-button-secondary" type="submit"><?php echo $icons->render('sparkles',array('decorative'=>true)); ?> Suggest mark with AI</button></form>
<?php endif; ?>
<?php if(!empty($suggestion)): ?><section class="chisimba-summary-card"><h3><?php echo $icons->render('sparkles',array('decorative'=>true)); ?> AI marking draft</h3>
<p><strong>AI suggested mark:</strong> <?php echo $suggestedMark; ?>%. This remains a read-only draft until a lecturer saves the final mark.</p>
<div class="chisimba-responsive-table"><table class="chisimba-table"><thead><tr><th>Criterion</th><th>Score</th><th>Reasoning</th></tr></thead><tbody>
<?php foreach((array)($suggestion['criteria']??array()) as $criterion): ?><tr><th scope="row"><?php echo $e($criterion['objective']??''); ?></th><td><?php echo (int)($criterion['score']??0); ?> / <?php echo (int)($criterion['maximumMark']??0); ?></td><td><?php echo $e($criterion['rationale']??''); ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<h4>Authorship and source discussion notes</h4><p>This is not an AI detector, does not estimate who wrote the Essay, and is not proof of AI use. These are optional discussion prompts and never affect the suggested mark.</p>
<?php if(!empty($review['observations'])): ?><ul><?php foreach((array)$review['observations'] as $observation): ?><li><?php echo $e($observation); ?></li><?php endforeach; ?></ul>
<?php if(!empty($review['questions'])): ?><p><strong>Possible review questions</strong></p><ul><?php foreach((array)$review['questions'] as $question): ?><li><?php echo $e($question); ?></li><?php endforeach; ?></ul><?php endif; ?>
<?php else: ?><p>No discussion points were generated. This does not establish who wrote the Essay.</p><?php endif; ?></section><?php endif; ?>
<form class="chisimba-form" method="post" enctype="multipart/form-data" action="<?php echo $e($this->uri(array('action'=>'uploadsubmit'))); ?>">
<input type="hidden" name="id" value="<?php echo $e($topic); ?>"><input type="hidden" name="book" value="<?php echo $e($book); ?>">
<?php if(!empty($suggestion)): ?>
<input type="hidden" name="use_ai_suggestion" value="1">
<fieldset><legend>Lecturer decision</legend>
<div class="chisimba-form-field"><label for="essay-lecturer-adjustment">Lecturer adjustment (percentage points)</label><input id="essay-lecturer-adjustment" name="lecturer_adjustment" type="number" min="-100" max="100" step="1" value="<?php echo $e($adjustment); ?>"><p class="form-help">Use a positive number to add points or a negative number to deduct points from the AI suggestion.</p></div>
<div class="chisimba-form-field"><label for="essay-adjustment-reason">Reason for adjustment</label><textarea id="essay-adjustment-reason" name="integrity_reason" rows="3"><?php echo $e($adjustmentReason); ?></textarea><p class="form-help">A reason is required whenever the adjustment is not zero.</p></div>
<div class="chisimba-summary-card" role="status"><strong>Calculated final mark: <output id="essay-final-mark"><?php echo $initialFinal; ?></output>%</strong></div>
</fieldset>
<?php else: ?>
<div class="chisimba-form-field"><label for="essay-mark">Final mark (%)</label><input id="essay-mark" name="mark" type="number" min="0" max="100" step="1" value="<?php echo $e($displayMark); ?>" required></div>
<?php endif; ?>
<div class="chisimba-form-field"><label for="essay-comment">Feedback</label><textarea id="essay-comment" name="comment" rows="7"><?php echo $e($displayComment); ?></textarea></div>
<div class="chisimba-form-field"><label for="essay-return-file">Returned document <span class="form-help">(optional)</span></label><input id="essay-return-file" name="file" type="file" accept=".pdf,.doc,.docx,.odt,.rtf,.txt"></div>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $icons->render('save',array('decorative'=>true)); ?> Save final mark</button><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'marktopic','id'=>$topic))); ?>"><?php echo $icons->render('x',array('decorative'=>true)); ?> Cancel</a></div>
</form></section>
<?php if(!empty($suggestion)): ?><script>(function(){var adjustment=document.getElementById('essay-lecturer-adjustment'),output=document.getElementById('essay-final-mark'),base=<?php echo $suggestedMark; ?>;function update(){var value=Math.max(-100,Math.min(100,Number(adjustment.value)||0));output.textContent=Math.max(0,Math.min(100,base+value));}adjustment.addEventListener('input',update);update();}());</script><?php endif; ?>
