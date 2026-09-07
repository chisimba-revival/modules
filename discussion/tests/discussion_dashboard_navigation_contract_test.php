<?php
$root=dirname(__DIR__);$dashboard=file_get_contents($root.'/classes/dynamicblocks_discussionview_class_inc.php');$navigation=file_get_contents($root.'/classes/block_discussionnavigation_class_inc.php');$composer=file_get_contents($root.'/classes/block_newtopic_class_inc.php');$checks=array(
'dashboard has one distinct action for opening and one for creating'=>substr_count($dashboard,"mod_discussion_open_discussion")===1&&substr_count($dashboard,"mod_discussion_startnewtopic")===1,
'dashboard uses skin icons'=>str_contains($dashboard,"getObject('iconservice','ui')")&&!str_contains($dashboard,"setIcon('notes')"),
'navigation is role aware'=>str_contains($navigation,'$mayManage')&&str_contains($navigation,"studentstarttopic"),
'marking navigation requires assessment status'=>str_contains($navigation,"assessment_enabled']??'N')==='Y'"),
'current navigation is not a link'=>str_contains($navigation,'aria-current="page"')&&str_contains($navigation,'discussion-nav__current'),
'administration keeps the discussion navigation panel'=>str_contains(file_get_contents($root.'/templates/content/discussion_administration.php'),'"block" : "discussionnavigation"'),
'composer subject uses full width style'=>str_contains(file_get_contents($root.'/resources/discussion-modern.css'),'.discussion-compose-modern #title'),
'composer language follows the site invisibly'=>str_contains($composer,'type="hidden" name="language"')&&!str_contains($composer,'discussion-compose__language-field'),
'composer choices and actions use skin icons'=>str_contains($composer,'discussion-choice__icon')&&str_contains($composer,"render('send'")&&str_contains($composer,"render('x'"),
);foreach($checks as $name=>$passed){if(!$passed){fwrite(STDERR,"FAIL: $name\n");exit(1);}}echo "Discussion dashboard and navigation contracts passed.\n";
?>
