<?php
$e=function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
$t=function($key,$fallback=''){return $this->objLanguage->languageText($key,'contextcontent',$fallback);};
$c=function($key,$fallback){return $this->objLanguage->code2Txt($key,'contextcontent',NULL,$fallback);};
$isEdit=$mode==='edit';
$formAction=$isEdit?'updatechapter':'savechapter';
$chapterTitle=$isEdit?(string)$chapter['chaptertitle']:'';
$visibility=$isEdit?(string)$chapter['visibility']:'Y';
$releaseDate=$isEdit&&!empty($chapter['releasedate'])?substr((string)$chapter['releasedate'],0,10):'';
$endDate=$isEdit&&!empty($chapter['enddate'])?substr((string)$chapter['enddate'],0,10):'';
$pageTitle=$isEdit?$c('mod_contextcontent_editchaptermodern','Edit [-chapter-]'):$c('mod_contextcontent_addchaptermodern','Add a new [-chapter-]');
$editor=$this->newObject('htmlarea','htmlelements');
$editor->name='intro';
$editor->context=TRUE;
$editor->value=$isEdit?(string)$chapter['introduction']:'';
$icons=$this->getObject('iconservice','ui');
$saveIcon=$icons->render('save',array('decorative'=>TRUE));
$cancelUrl=$this->uri(array('action'=>$isEdit?'viewchapter':'showcontextchapters','id'=>$isEdit?$id:NULL));
?>
<div class="chisimba-form-page contextcontent-chapter-form-page">
<section class="chisimba-form-card chisimba-form-card--wide" aria-labelledby="contextcontent-chapter-form-title">
<header class="chisimba-form-card__header">
<p class="contextcontent-form-eyebrow"><?php echo $e($this->objContext->getTitle()); ?></p>
<h1 id="contextcontent-chapter-form-title"><?php echo $e(ucfirst($pageTitle)); ?></h1>
<p><?php echo $e($c('mod_contextcontent_chapterformhelp','Set the title, availability and introduction for this [-chapter-].')); ?></p>
</header>
<?php if($this->getParam('stage_gate_saved','')==='1'): ?>
<div class="confirmation contextcontent-stage-gate-confirmation"><?php echo $e($t('mod_contextcontent_stage_gate_saved','Stage gate saved.')); ?></div>
<?php endif; ?>
<form class="chisimba-form chisimba-form--wide" method="post" action="<?php echo $e($this->uri(array('action'=>$formAction))); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($contextContentCsrf); ?>" />
<?php if($isEdit): ?>
<input type="hidden" name="id" value="<?php echo $e($id); ?>" />
<input type="hidden" name="chaptercontentid" value="<?php echo $e($chapter['id']); ?>" />
<input type="hidden" name="contextchapterid" value="<?php echo $e($chapter['contextchapterid']); ?>" />
<?php endif; ?>
<div class="chisimba-form-grid contextcontent-chapter-details">
<div class="chisimba-form-field chisimba-form-field--full">
<label for="input_chaptertitle"><?php echo $e(ucfirst($c('mod_contextcontent_chaptertitlemodern','[-chapter-] title'))); ?></label>
<input id="input_chaptertitle" type="text" name="chaptertitle" maxlength="255" required value="<?php echo $e($chapterTitle); ?>" />
</div>
<?php if(!empty($sectionsEnabled)): ?>
<div class="chisimba-form-field">
<label for="input_sectionid"><?php echo $e(ucfirst($c('mod_contextcontent_sectionlabel','[-section-]'))); ?></label>
<select id="input_sectionid" name="sectionid">
<option value=""><?php echo $e($t('mod_contextcontent_unassigned','Not assigned')); ?></option>
<?php foreach((array)$availableSections as $availableSection): ?>
<option value="<?php echo $e($availableSection['id']); ?>"<?php echo $isEdit&&(string)($chapter['sectionid']??'')===(string)$availableSection['id']?' selected':''; ?>><?php echo $e($availableSection['title']); ?></option>
<?php endforeach; ?>
</select>
</div>
<?php endif; ?>
<fieldset class="chisimba-form-field chisimba-choice-field<?php echo empty($sectionsEnabled)?' chisimba-form-field--full':''; ?>">
<legend><?php echo $e(ucfirst($this->objLanguage->code2Txt('mod_contextcontent_visibletostudents','contextcontent',NULL,'Visible to [-readonlys-]'))); ?></legend>
<div class="chisimba-choice-group">
<?php foreach(array('Y'=>$this->objLanguage->languageText('word_yes','system','Yes'),'N'=>$this->objLanguage->languageText('word_no','system','No'),'I'=>$t('mod_contextcontent_onlyshowintroduction','Only show introduction')) as $value=>$label): ?>
<label><input type="radio" name="visibility" value="<?php echo $e($value); ?>"<?php echo $visibility===$value?' checked':''; ?> /> <?php echo $e($label); ?></label>
<?php endforeach; ?>
</div>
</fieldset>
<div class="chisimba-form-field">
<label for="input_startdate"><?php echo $e($t('mod_contextcontent_releasedate','Release date')); ?></label>
<input id="input_startdate" type="date" name="startdate" value="<?php echo $e($releaseDate); ?>" />
</div>
<div class="chisimba-form-field">
<label for="input_enddate"><?php echo $e($t('mod_contextcontent_enddate','End date')); ?></label>
<input id="input_enddate" type="date" name="enddate" value="<?php echo $e($endDate); ?>" />
</div>
</div>
<section class="chisimba-form-section">
<h2><?php echo $e($t('mod_contextcontent_sectionintroduction','Introduction')); ?></h2>
<p class="contextcontent-field-help"><?php echo $e($c('mod_contextcontent_chapterintrohelp','Introduce this [-chapter-] and explain what the reader will learn.')); ?></p>
<?php echo $editor->show(); ?>
</section>
<?php if($isEdit):
$stageGateTests=isset($stageGateTests)&&is_array($stageGateTests)?$stageGateTests:array();
$selectedTest=(string)($chapter['stage_gate_testid']??'');
$passMark=!empty($chapter['stage_gate_passmark'])?(int)$chapter['stage_gate_passmark']:70;
?>
<section class="chisimba-form-section contextcontent-stage-gate-card">
<h2><?php echo $e($t('mod_contextcontent_stage_gate_heading','Chapter assessment')); ?></h2>
<?php if(!empty($selectedStageGateIsInvalid)): ?><div class="error"><?php echo $e($t('mod_contextcontent_stage_gate_existing_summative','The selected assessment cannot be used as a chapter gate.')); ?></div><?php endif; ?>
<div class="chisimba-form-grid">
<div class="chisimba-form-field">
<label for="input_stage_gate_testid"><?php echo $e($t('mod_contextcontent_stage_gate_test','Assessment')); ?></label>
<select id="input_stage_gate_testid" name="stage_gate_testid"><option value=""><?php echo $e($t('mod_contextcontent_stage_gate_choose_test','No chapter assessment')); ?></option>
<?php foreach($stageGateTests as $test): if(empty($test['id'])||!isset($test['name']))continue; ?><option value="<?php echo $e($test['id']); ?>"<?php echo $selectedTest===(string)$test['id']?' selected':''; ?>><?php echo $e($test['name']); ?></option><?php endforeach; ?>
</select>
</div>
<div class="chisimba-form-field chisimba-form-field--compact">
<label for="input_stage_gate_passmark"><?php echo $e($t('mod_contextcontent_stage_gate_passmark','Pass mark (%)')); ?></label>
<input id="input_stage_gate_passmark" type="number" min="1" max="100" name="stage_gate_passmark" value="<?php echo $e($passMark); ?>" />
</div>
</div>
</section>
<?php endif; ?>
<div class="chisimba-form-actions">
<button type="submit" class="button primary"><span class="chisimba-button-icon"><?php echo $saveIcon; ?></span><span><?php echo $e($isEdit?$t('mod_contextcontent_savechanges','Save changes'):$c('mod_contextcontent_createchaptermodern','Create [-chapter-]')); ?></span></button>
<a class="button chisimba-button-secondary" href="<?php echo $e($cancelUrl); ?>"><?php echo $e($this->objLanguage->languageText('word_cancel','system','Cancel')); ?></a>
</div>
</form>
</section>
</div>
<style>
.contextcontent-chapter-form-page{max-width:72rem;margin:0 auto}.contextcontent-form-eyebrow{margin:0 0 .35rem;color:var(--chisimba-primary);font-size:.78rem;font-weight:750;letter-spacing:.06em;text-transform:uppercase}.contextcontent-chapter-details{margin-top:1.5rem}.contextcontent-field-help{margin-top:-.35rem;color:var(--chisimba-text-muted)}.contextcontent-stage-gate-card{padding:1.15rem;border:1px solid var(--chisimba-border);border-radius:.75rem;background:var(--chisimba-surface-subtle)}.chisimba-button-icon{display:inline-flex;align-items:center}.chisimba-button-icon svg{width:1rem;height:1rem}
</style>
