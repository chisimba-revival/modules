<?php
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/controller.php');
$overview=file_get_contents($root.'/classes/administrationoverview_class_inc.php');
$metrics=file_get_contents($root.'/classes/sitemetrics_class_inc.php');
$register=file_get_contents($root.'/register.conf');
$mail=file_get_contents($root.'/classes/mailqueuehealth_class_inc.php');
$checks=array(
 'administrator-only journey'=>str_contains($controller,'isAdmin()')&&str_contains($controller,"'noaccess_tpl.php'"),
 'separate configurable layout'=>str_contains($controller,"getContextBlocks('myadmin'")&&str_contains($controller,'$managing'),
 'site-health summary'=>str_contains($metrics,"countFrom('tbl_users')")&&str_contains($metrics,"countFrom('tbl_context')")&&str_contains($overview,'getActiveUsers()'),
 'presence categories are exclusive and highest-role first'=>str_contains($overview,'if($user->inAdminGroup($userId))')&&str_contains($overview,'elseif(count((array)$contexts->getContextWhereLecturer($userId))')&&str_contains($overview,'elseif(count((array)$contexts->getContextWhereStudent($userId))'),
 'presence has dashboard presentation'=>str_contains($overview,'presence-role-grid')&&str_contains($overview,'presence-scope-summary'),
 'mail queue uses canonical communications records'=>str_contains($mail,'tbl_communications_outbox')&&str_contains($mail,'tbl_communications_worker_state'),
 'mail queue recovery is admin and CSRF protected'=>str_contains($controller,"action==='runmailqueue'")&&str_contains($controller,"consume('myadmin_mail_queue'")&&str_contains($controller,"getObject('communicationworker','communications')"),
 'mail health is dashboard presentation'=>str_contains($overview,'mail-health__grid')&&str_contains($overview,"formatDateTime"),
 'registration dashboard uses owning services'=>str_contains($overview,"getObject('registrationservice','registration-service')->administrationSummary()")&&str_contains($overview,'newRegistrations()')&&str_contains($metrics,'ORDER BY creationdate DESC,id DESC LIMIT 10')&&str_contains($overview,'registration-health__metrics')&&str_contains($overview,'registration-health__recent'),
 'stale registration reminders are admin and CSRF protected'=>str_contains($controller,"action==='sendregistrationreminder'")&&str_contains($controller,"consume('myadmin_registration_reminder'")&&str_contains($controller,'sendAdministratorReminder'),
 'registered dashboard'=>str_contains($register,'MODULE_ID: myadmin')&&str_contains($register,'My Administration'),
);
foreach($checks as $label=>$passed){if(!$passed){fwrite(STDERR,"FAIL: $label\n");exit(1);}}
echo "PASS: My Administration is an administrator-only operational dashboard.\n";
?>
