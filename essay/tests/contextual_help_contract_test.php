<?php
$root=dirname(__DIR__);$provider=file_get_contents($root.'/classes/helpcontent_class_inc.php');$template=file_get_contents($root.'/templates/content/manage_upload_tpl.php');$register=file_get_contents($root.'/register.conf');
$renderer=file_get_contents(dirname(dirname($root)).'/framework/app/core_modules/help/classes/contextualhelp_class_inc.php');
$checks=array(
'marking page exposes contextual help'=>str_contains($template,"->show('essay','ai-assisted-marking')"),
'topic is restricted to authorised markers'=>str_contains($provider,"isCourseAdmin(\$contextCode)")&&str_contains($provider,'isAdmin()'),
'student submission pages do not expose author help'=>substr_count($template,'ai-assisted-marking')===1,
'AI draft remains lecturer controlled'=>str_contains($register,'AI never publishes an Essay mark')&&str_contains($register,'The saved decision is authoritative'),
'authorship notes are not represented as detection'=>str_contains($register,'They are not an AI detector'),
'all proof content belongs to the language system'=>substr_count($register,'TEXT: mod_essay_help_ai_')>=15,
'full guide stays beside the marking task'=>str_contains($renderer,'chisimba-contextual-help-drawer')&&!str_contains($renderer,"'action' => 'topic'"),
);
$failed=false;foreach($checks as $name=>$ok){echo($ok?'PASS: ':'FAIL: ').$name.PHP_EOL;$failed=$failed||!$ok;}exit($failed?1:0);
