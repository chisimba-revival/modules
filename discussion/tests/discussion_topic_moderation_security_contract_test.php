<?php
/** Topic moderation security and UI source contract. @author Derek Keats */
$root=dirname(__DIR__);$controller=file_get_contents($root.'/controller.php');$block=file_get_contents($root.'/classes/block_topicmoderation_class_inc.php');$register=file_get_contents($root.'/register.conf');
$mutations=array('changetopicstatus','moderate_deletetopic','moderate_movetotangent','moderate_movetodiscussion','moderate_movetonewtopic','moderate_topicsticky');
$checks=array(
    'semantic moderation workspace'=>str_contains($block,'discussion-moderation-grid')&&!str_contains(substr($block,strpos($block,'function buildForm()')),'Return to Topic'),
    'topic title is destination'=>str_contains($block,'<h1><a href=')&&str_contains($block,"'action'=>'viewtopic'"),
    'discussion title is destination'=>str_contains($block,"'action'=>'discussion'")&&str_contains($block,'discussion_name'),
    'all forms carry scoped csrf'=>substr_count($block,"\$token('")>=5&&str_contains($controller,'validModerationMutation'),
    'post only mutations'=>str_contains($controller,"validManagementMutation('disc_mod_"),
    'course scope covers mutations'=>array_reduce($mutations,fn($ok,$action)=>$ok&&substr_count($controller,"'".$action."'")>=3,true),
    'explicit role rules cover mutations'=>array_reduce($mutations,fn($ok,$action)=>$ok&&substr_count($register,$action)>=2,true),
    'move destinations validated'=>str_contains($controller,'sameDiscussionTopic')&&str_contains($controller,"discussion_context"),
    'status values allow listed'=>str_contains($controller,"in_array(\$status,array('OPEN','CLOSE'),true)"),
    'sticky values allow listed'=>str_contains($controller,"in_array(\$sticky,array('0','1'),true)"),
    'lock reason stored as text'=>str_contains($controller,'trim(strip_tags')&&str_contains($controller,'mb_substr'),
    'assessed moderation hides topic deletion'=>str_contains($block,'discussion-moderation--assessed')&&str_contains($controller,"isAssessedDiscussion(\$topicInfo['discussion_id'])"),
    'assessed topics cannot be moved'=>substr_count($controller,'$this->isAssessedDiscussion($topicInfo[')>=3,
    'assessed reply deletion is author limited'=>str_contains($controller,'mayDeleteOwnPost')&&str_contains($controller,'<=120')&&str_contains($controller,"hash_equals((string)\$this->userId"),
    'own deletion is post and csrf scoped'=>str_contains($controller,"'deleteownpost'")&&str_contains($controller,"'disc_own_'.hash('sha256',\$id)"),
    'legacy ajax deletion retired'=>str_contains($controller,'Retired insecure AJAX deletion path'),
    'modern identities join by stable id'=>str_contains(file_get_contents($root.'/classes/dbpost_class_inc.php'),'tbl_discussion_post.userId = tbl_users.id'),
    'Derek Keats retained'=>str_contains($register,'Derek Keats')
);
$failed=array_keys(array_filter($checks,fn($passed)=>!$passed));if($failed){fwrite(STDERR,'Failed: '.implode('; ',$failed).PHP_EOL);exit(1);}echo 'Discussion topic moderation security and UI contracts passed ('.count($checks)." checks).\n";
