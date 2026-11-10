<?php
/** Native event form; failed submissions retain draft values and original revision. */
$escape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$text=fn($key)=>$this->objLanguage->languageText('mod_calendar_'.$key,'calendar');
$action=$deleteMode?'delete':($draft['id']!==''?'updateevent':'saveevent');
if($this->getObject('modules','modulecatalogue')->checkIfRegistered('help'))echo $this->getObject('contextualhelp','help')->show('calendar','editing-events');
?>
<section class="chisimba-form-page"><div class="chisimba-form-card chisimba-form-card--wide">
<h1><?php echo $escape($text($deleteMode?'secure_delete':($draft['id']!==''?'secure_edit':'secure_add'))); ?></h1>
<?php if($calendarError!==''): ?><p class="error" role="alert"><?php echo $escape($text($calendarError)); ?></p><?php endif; ?>
<form method="post" action="<?php echo $escape($this->uri(['action'=>$action],'calendar')); ?>">
<?php foreach(['id','revision','creationid','contextcode'] as $key): ?><input type="hidden" name="<?php echo $key; ?>" value="<?php echo $escape($draft[$key]); ?>"><?php endforeach; ?>
<input type="hidden" name="csrf_token" value="<?php echo $escape($calendarToken); ?>">
<?php if($deleteMode): ?>
<p><?php echo $escape($draft['title']); ?></p><p><?php echo $escape($text('secure_delete_warning')); ?></p>
<?php else: ?>
<?php if($draft['id']===''): ?><div class="chisimba-form-field"><label for="calendar-scope"><?php echo $escape($text('secure_scope')); ?></label><select id="calendar-scope" name="eventfor">
<?php foreach(['0'=>'secure_personal','1'=>'secure_course','3'=>'secure_site'] as $value=>$label){try{$calendarEditor->scope((string)$value);}catch(Throwable $e){continue;}echo '<option value="'.$value.'"'.($draft['eventfor']===(string)$value?' selected':'').'>'.$escape($this->objLanguage->code2Txt('mod_calendar_'.$label,'calendar')).'</option>';} ?>
</select></div><?php else: ?><input type="hidden" name="eventfor" value="<?php echo $escape($draft['eventfor']); ?>"><?php endif; ?>
<div class="chisimba-form-grid">
<?php foreach(['title'=>['secure_title','text'],'date'=>['secure_start','date'],'date2'=>['secure_end','date'],'timefrom'=>['secure_from','time'],'timeto'=>['secure_to','time'],'url'=>['secure_url','url']] as $key=>$field): ?>
<div class="chisimba-form-field<?php echo in_array($key,['title','url'],true)?' chisimba-form-field--full':''; ?>"><label for="calendar-<?php echo $key; ?>"><?php echo $escape($text($field[0])); ?></label><input id="calendar-<?php echo $key; ?>" name="<?php echo $key; ?>" type="<?php echo $field[1]; ?>" value="<?php echo $escape($draft[$key]); ?>" <?php echo $key==='url'?'maxlength="100"':($key==='title'?'required maxlength="100"':'required'); ?>></div>
<?php endforeach; ?>
</div>
<div class="chisimba-form-field"><label for="details"><?php echo $escape($text('secure_details')); ?></label>
<?php $rich=$this->newObject('htmlarea','htmlelements');$rich->setName('details');$rich->setContent($draft['details']);echo $rich->show(); ?>
</div>
<?php endif; ?>
<div class="chisimba-form-actions"><button type="submit"><?php echo $escape($text($deleteMode?'secure_delete':'secure_save')); ?></button><a class="button chisimba-button-secondary" href="<?php echo $escape($this->uri(null,'calendar')); ?>"><?php echo $escape($this->objLanguage->languageText('word_cancel')); ?></a>
<?php if($draft['id']!==''&&!$deleteMode): ?><a href="<?php echo $escape($this->uri(['action'=>'edit','id'=>$draft['id']],'calendar')); ?>" target="_blank" rel="noopener"><?php echo $escape($text('secure_check_saved')); ?></a><?php endif; ?>
</div></form></div></section>
