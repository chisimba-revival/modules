<?php
$root=dirname(__DIR__);$provider=file_get_contents($root.'/classes/helpcontent_class_inc.php');$write=file_get_contents($root.'/templates/content/write_tpl.php');$upload=file_get_contents($root.'/templates/content/upload_tpl.php');$register=file_get_contents($root.'/register.conf');
$checks=array(
'online writer exposes submission Help'=>str_contains($write,"->show('essay','submitting-an-essay')"),
'document upload exposes the same Help'=>str_contains($upload,"->show('essay','submitting-an-essay')"),
'topic uses the current context student permission'=>str_contains($provider,'isContextStudent($contextCode)'),
'draft and submission remain distinct'=>str_contains($register,'A saved draft remains editable and is not available for marking until you submit it'),
'upload is described as immediate submission'=>str_contains($register,'Uploading submits that file immediately'),
'author terminology uses systext'=>str_contains($register,'available to the [-author-] for marking'),
'all topic content belongs to the language system'=>substr_count($register,'TEXT: mod_essay_help_submit_')>=15,
);
$failed=false;foreach($checks as $name=>$ok){echo($ok?'PASS: ':'FAIL: ').$name.PHP_EOL;$failed=$failed||!$ok;}exit($failed?1:0);
