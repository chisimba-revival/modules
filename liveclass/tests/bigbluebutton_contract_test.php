<?php
$provider=file_get_contents(__DIR__.'/../classes/bigbluebuttonprovider_class_inc.php');$controller=file_get_contents(__DIR__.'/../controller.php');$register=file_get_contents(__DIR__.'/../register.conf');
$checks=array(
 'BBB signing keeps the shared secret off the URL'=>str_contains($provider,"sha1(\$call.\$query.\$this->secret())")&&!str_contains($provider,"'secret'=>"),
 'meeting creation and role joins are server controlled'=>str_contains($provider,"'attendeePW'")&&str_contains($provider,"'moderatorPW'")&&str_contains($controller,'$this->auth->canManage($session)'),
 'mutations require POST and CSRF'=>str_contains($controller,"REQUEST_METHOD")&&str_contains($controller,"csrf->consume"),
 'context language remains abstract'=>str_contains($register,'Live [-context-]')&&!preg_match('/TEXT:.*\|.*\|[^\n]*(course|student|instructor)/i',$register),
 'provider credentials are module configuration'=>str_contains($register,'LIVECLASS_BIGBLUEBUTTON_ENDPOINT')&&str_contains($register,'LIVECLASS_BIGBLUEBUTTON_SECRET'),
);
$failed=array_keys(array_filter($checks,fn($ok)=>!$ok));foreach($checks as $name=>$ok)echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;exit($failed?1:0);
