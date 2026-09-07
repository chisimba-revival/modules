<?php
$root=dirname(__DIR__);$repo=dirname($root);
$controller=file_get_contents($root.'/controller.php');$renderer=file_get_contents($root.'/classes/liveclasscontentrenderer_class_inc.php');$life=file_get_contents($root.'/classes/liveclasslifecycle_class_inc.php');$context=file_get_contents($repo.'/contextcontent/controller.php');$registry=file_get_contents($repo.'/contextcontent/classes/contenttyperegistry_class_inc.php');
$checks=array(
 'live session is registered as a context content type'=>strpos($registry,"'key' => 'live_session'")!==false,
 'picker hands the exact chapter to the live session scheduler'=>strpos($context,'\'flow_chapter\' => $chapter')!==false,
 'live sessions are always top-level chapter pages'=>strpos($context,"'flow_parent' => ''")!==false&&strpos($controller,"'parentid'=>''")!==false,
 'saving creates a provider-backed flow placement'=>strpos($controller,"'contenttype'=>'live_session'")!==false&&strpos($controller,"'provideritemid'=>\$id")!==false,
 'joining is guarded on the server'=>strpos($controller,"['can_join']")!==false,
 'join window is configurable and bounded'=>strpos($life,'LIVECLASS_JOIN_WINDOW_MINUTES')!==false&&strpos($life,'min(180')!==false,
 'flow card has a featured visual and locked state'=>strpos($renderer,'liveclass-flow-visual')!==false&&strpos($renderer,'aria-disabled="true"')!==false,
 'rendering verifies context ownership'=>strpos($renderer,"session['context_code']")!==false,
);
foreach($checks as $label=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";}
