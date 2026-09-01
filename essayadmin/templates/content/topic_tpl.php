<?php
/** Accessible topic-area authoring form using shared skin primitives. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$icons=$this->getObject('iconservice','ui');
$row=!empty($data[0])?$data[0]:array();
$id=(string)($row['id']??'');
$closing=(string)($row['closing_date']??date('Y-m-d H:i:s',strtotime('+7 days')));
$closingLocal=$this->objTimeAndDate->inTimezone($closing);
if($closingLocal===null){$closingLocal=new DateTimeImmutable('+7 days',new DateTimeZone($this->objTimeAndDate->siteTimezone()));}
$closingDay=(int)$closingLocal->format('j');$closingMonth=(int)$closingLocal->format('n');$closingYear=(int)$closingLocal->format('Y');$closingTime=$closingLocal->format('H:i');
$cancel=$id!==''?$this->uri(array('action'=>'view','id'=>$id)):$this->uri(array());
?>
<form class="chisimba-form chisimba-workspace" method="post" action="<?php echo $e($this->uri(array('action'=>'savetopic'))); ?>">
<?php if($id!==''): ?><input type="hidden" name="id" value="<?php echo $e($id); ?>"><?php endif; ?>
<div class="chisimba-form-field"><label for="essay-topic-area">Topic area</label><input id="essay-topic-area" name="topicarea" value="<?php echo $e($row['name']??''); ?>" required maxlength="255"></div>
<div class="chisimba-form-field"><label for="essay-topic-description">Description</label><textarea id="essay-topic-description" name="description" rows="4"><?php echo $e($row['description']??''); ?></textarea></div>
<div class="chisimba-form-field"><label for="essay-topic-instructions"><?php echo $e($this->objLanguage->code2Txt('mod_essayadmin_instructions','essayadmin')); ?></label><textarea id="essay-topic-instructions" name="instructions" rows="5"><?php echo $e($row['instructions']??''); ?></textarea></div>
<fieldset class="chisimba-form-field"><legend>Closing date and time</legend><div class="chisimba-date-time-fields"><label for="essay-closing-day">Day</label><select id="essay-closing-day" name="closing_day"><?php for($day=1;$day<=31;$day++): ?><option value="<?php echo $day; ?>"<?php echo $day===$closingDay?' selected':''; ?>><?php echo $day; ?></option><?php endfor; ?></select><label for="essay-closing-month">Month</label><select id="essay-closing-month" name="closing_month"><?php for($month=1;$month<=12;$month++): ?><option value="<?php echo $month; ?>"<?php echo $month===$closingMonth?' selected':''; ?>><?php echo $e(date('F',mktime(0,0,0,$month,1))); ?></option><?php endfor; ?></select><label for="essay-closing-year">Year</label><select id="essay-closing-year" name="closing_year"><?php for($year=(int)date('Y');$year<=(int)date('Y')+10;$year++): ?><option value="<?php echo $year; ?>"<?php echo $year===$closingYear?' selected':''; ?>><?php echo $year; ?></option><?php endfor; ?></select><label for="essay-closing-time">Time (24-hour)</label><input id="essay-closing-time" type="time" name="closing_time" value="<?php echo $e($closingTime); ?>" required></div></fieldset>
<div class="chisimba-form-field"><label for="essay-topic-percentage">Percentage of year mark</label><input id="essay-topic-percentage" type="number" name="percentage" min="0" max="100" step="1" value="<?php echo $e($row['percentage']??0); ?>"></div>
<label><input type="checkbox" name="force"<?php echo !empty($row['forceone'])?' checked':''; ?>> Force one student per essay</label>
<label><input type="checkbox" name="bypass"<?php echo !empty($row['bypass'])?' checked':''; ?>> Bypass closing date</label>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $icons->render('save',array('decorative'=>true)); ?> Save topic area</button><a class="button chisimba-button-secondary" href="<?php echo $e($cancel); ?>"><?php echo $icons->render('x',array('decorative'=>true)); ?> Cancel</a></div>
</form>
