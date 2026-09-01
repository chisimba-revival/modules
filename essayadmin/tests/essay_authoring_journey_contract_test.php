<?php
/** Contract for the lecturer Essay authoring journey. @author Derek Keats */
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/controller.php');
$topic=file_get_contents($root.'/templates/content/topic_tpl.php');
$essay=file_get_contents($root.'/templates/content/addeditessay_tpl.php');
$checks=array(
 'saving an essay persists provider-owned data'=>str_contains($controller,'$this->dbessays->addEssay($fields'),
 'essay save validates course scope'=>str_contains($controller,"(string)\$topic[0]['context'] !== (string)\$this->contextcode"),
 'topic form has accessible named fields'=>str_contains($topic,'for="essay-topic-area"')&&str_contains($topic,'for="essay-closing-day"'),
 'closing date is unambiguous'=>str_contains($topic,'name="closing_day"')&&str_contains($topic,'name="closing_month"')&&!str_contains($topic,'datetime-local'),
 'topic form uses skin actions and icons'=>str_contains($topic,'chisimba-form-actions')&&str_contains($topic,"render('save'"),
 'essay form has accessible named fields'=>str_contains($essay,'for="essay-title"')&&str_contains($essay,'for="essay-notes"'),
 'essay form has one clear save and cancel journey'=>str_contains($essay,'Save essay')&&str_contains($essay,'chisimba-button-secondary'),
);
foreach($checks as $label=>$ok){if(!$ok){fwrite(STDERR,'FAIL: '.$label.PHP_EOL);exit(1);}echo 'PASS: '.$label.PHP_EOL;}
