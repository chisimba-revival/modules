<?php
/** Disposable local database checks; no provider calls. */
if(PHP_SAPI!=='cli'||getenv('QUESTIONWORKSHOP_LOCAL_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$engine=new engine();
$store=$engine->getObject('examstore','mcqgenerator');$sets=$engine->getObject('workshopstore','mcqgenerator');$id=null;$sourceId=null;
try {
 $content=['instructions'=>'Example 🌿','headings'=>true,'reviewed'=>false,'questions'=>[]];
 $id=$store->createExam('local-exam-fixture','QA database exam',$content);$row=$store->one($id);
 if(json_decode($row['content_json'],true)['instructions']!=='Example 🌿')throw new RuntimeException('Unicode lost');
 $store->saveExam($row,'QA changed exam',$content);
 try{$store->saveExam($row,'Stale edit',$content);throw new RuntimeException('Stale revision accepted');}catch(DomainException $e){if($e->getMessage()!=='exam_changed')throw $e;}
 $other=$store->one($id);$other['ownerid']='another-owner';
 try{$store->saveExam($other,'Wrong owner',$content);throw new RuntimeException('Wrong owner accepted');}catch(DomainException $e){if($e->getMessage()!=='exam_changed')throw $e;}
 if($store->owned('another-owner'))throw new RuntimeException('Owner list leak');
 $store->removeOwned($store->one($id));if($store->one($id)!==null)throw new RuntimeException('Delete failed');$id=null;
 echo "PASS real database Unicode, revision conflicts, owner boundary and deletion.\n";
}finally{if($id)$store->removeOwned($store->one($id));}
