<?php
/** Contract for the lecturer Essay authoring journey. @author Derek Keats */
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/classes/essaymanagementbase_class_inc.php');
$topic=file_get_contents($root.'/templates/content/manage_topic_tpl.php');
$essay=file_get_contents($root.'/templates/content/manage_addeditessay_tpl.php');
$checks=array(
 'saving an essay persists provider-owned data'=>str_contains($controller,'$this->dbessays->addEssay($fields'),
 'essay save validates course scope'=>str_contains($controller,"(string)\$topic[0]['context'] !== (string)\$this->contextcode"),
 'topic form has accessible named fields'=>str_contains($topic,'for="essay-topic-area"')&&str_contains($topic,"getObject('datetimepicker'"),
 'closing date uses shared primitive'=>str_contains($topic,"setName('closing')")&&!str_contains($topic,'closing_day'),
 'year mark belongs to Gradebook'=>!str_contains($topic,'percentage')&&!str_contains($controller,"getParam('percentage'"),
 'topic form uses skin actions and icons'=>str_contains($topic,'chisimba-form-actions')&&str_contains($topic,"render('save'"),
 'essay form has accessible named fields'=>str_contains($essay,'for="essay-title"')&&str_contains($essay,'essay-guidance-heading'),
 'essay form has one clear save and cancel journey'=>str_contains($essay,'Save essay')&&str_contains($essay,'chisimba-button-secondary'),
 'topic view uses skin detail and action primitives'=>str_contains($controller,'chisimba-details')
    &&str_contains($controller,'chisimba-section-heading')&&str_contains($controller,'chisimba-row-actions'),
);
foreach($checks as $label=>$ok){if(!$ok){fwrite(STDERR,'FAIL: '.$label.PHP_EOL);exit(1);}echo 'PASS: '.$label.PHP_EOL;}
