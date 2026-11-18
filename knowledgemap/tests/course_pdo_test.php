<?php
/** Disposable PDO integration: real map/context/page/quiz persistence, synthetic identity services. @author Derek Keats */
if (PHP_SAPI!=='cli' || getenv('CHISIMBA_DB_BASELINE')!=='1') exit(64);
set_include_path('/framework/app'.PATH_SEPARATOR.get_include_path());
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public function getObject($name,$module=null) { return $GLOBALS['objects'][$name]??throw new RuntimeException('Missing fixture object '.$name); }
    public function getSession($key) { return 'original'; }
    public function setSession($key,$value) { throw new RuntimeException('Provisioning switched the editor session'); }
    public function unsetSession($key) { throw new RuntimeException('Provisioning cleared the editor session'); }
}
class controller extends ChisimbaObject {}
require '/framework/app/classes/core/chisimbadatabaseconnection.php';
require '/framework/app/classes/core/dbtable_class_inc.php';
require '/framework/app/classes/core/chisimbadatabaseschema.php';
$db=ChisimbaDatabaseConnection::fromLegacyDsn(['phptype'=>'mysqli','hostspec'=>'baseline-db:3306','username'=>'root','password'=>getenv('CHISIMBA_DB_PASSWORD'),'database'=>'contract_fixture','charset'=>'utf8mb4']);
function bindTable($class,$table) {
    global $db; $object=new $class();
    (new ReflectionProperty(dbTable::class,'_db'))->setValue($object,$db);
    $object->dbLayer='PDO';$object->dbType='mysqli';$object->_tableName=$table;$object->_serverName='fixture';
    $GLOBALS['objects'][$class]=$object;return $object;
}
function inject($object,$property,$value) { (new ReflectionProperty($object,$property))->setValue($object,$value); }
function verify($ok,$message) { if (!$ok) throw new RuntimeException($message); }
function rejects($fn,$code) { try { $fn(); } catch (DomainException $e) { verify($e->getMessage()===$code,'Expected '.$code.', got '.$e->getMessage());return; } throw new RuntimeException('Expected rejection '.$code); }
$table=bindTable('dbTable','fixture_groups'); $schema=new ChisimbaDatabaseSchema($table);
$defs=array_merge(glob('/modules/knowledgemap/sql/*.sql'),array_map(fn($name)=>'/modules/contextcontent/sql/tbl_contextcontent_'.$name.'.sql',['sections','chapters','chaptercontext','chaptercontent','titles','pages','order']),[
 '/modules/mcqtests/sql/tbl_tests.sql','/modules/mcqtests/sql/tbl_test_questions.sql',
 '/framework/app/core_modules/context/sql/tbl_context.sql','/framework/app/core_modules/context/sql/tbl_contextmodules.sql',
 '/framework/app/core_modules/context/sql/tbl_context_blocks.sql']);
foreach ($defs as $definition) { $fields=$options=$tableIndexes=[];include $definition;$schema->createTable($tablename,$fields,$options);foreach ($tableIndexes as $name=>$index)$schema->createIndex($tablename,$name,$index); }
$schema->createTable('fixture_groups',['id'=>['type'=>'text','length'=>32],'code'=>['type'=>'text','length'=>64]]);
foreach (['dbknowledgemaps','dbknowledgemapnodes','dbknowledgemaprelationships','knowledgemapservice','knowledgemapgraphservice','knowledgemapblueprint','knowledgemapcourseservice'] as $class) require '/modules/knowledgemap/classes/'.$class.'_class_inc.php';
foreach (['dbcourseoutline','db_contextcontent_titles','db_contextcontent_pages','db_contextcontent_order','contentauthoringservice','contenttyperegistry'] as $class) require '/modules/contextcontent/classes/'.$class.'_class_inc.php';
require '/modules/mcqtests/classes/quizoutlineservice_class_inc.php';
foreach (['dbcontext','dbcontextmodules','dbcontextblocks'] as $class) require '/framework/app/core_modules/context/classes/'.$class.'_class_inc.php';
$user=new class { public function userId(){return 'author';} public function isLoggedIn(){return true;} public function isAdmin(){return true;} };
$GLOBALS['objects']['user']=$user;
$GLOBALS['objects']['language']=new class { public function languageText(...$args){return $args[0];} };
$GLOBALS['objects']['managegroups']=new class { public function createGroups($code,$title){return $GLOBALS['objects']['dbTable']->insert(['code'=>$code])!==false;} };
$authors=new class { public $create=true,$manage=true;public function canCreateContext($id){return $this->create;}public function canManageContext($code,$id){return $this->manage;} };
$GLOBALS['objects']['contextauthorservice']=$authors;
$auth=new class { public $manage=true;public function allows($map,$permission){return $this->manage;} };
$GLOBALS['objects']['knowledgemapauthorizationservice']=$auth;
$maps=bindTable('dbknowledgemaps','tbl_knowledgemap_maps');
$nodes=bindTable('dbknowledgemapnodes','tbl_knowledgemap_nodes');$edges=bindTable('dbknowledgemaprelationships','tbl_knowledgemap_relationships');
$contexts=bindTable('dbcontext','tbl_context');$contexts->objUser=$user;
$modules=bindTable('dbcontextmodules','tbl_contextmodules');
$blocks=bindTable('dbcontextblocks','tbl_context_blocks');$blocks->objUser=$user;
$outline=bindTable('dbcourseoutline','tbl_contextcontent_sections');
$quizzes=bindTable('quizoutlineservice','tbl_tests');
$titles=bindTable('db_contextcontent_titles','tbl_contextcontent_titles');$titles->objUser=$user;
$pages=bindTable('db_contextcontent_pages','tbl_contextcontent_pages');$pages->objUser=$user;
$order=bindTable('db_contextcontent_order','tbl_contextcontent_order');$order->objUser=$user;
$registry=new contenttyperegistry();$registry->init();$GLOBALS['objects']['contenttyperegistry']=$registry;
$pageService=new contentauthoringservice();$pageService->titles=$titles;$pageService->pages=$pages;$pageService->order=$order;$pageService->registry=$registry;
$GLOBALS['objects']['contentauthoringservice']=$pageService;
foreach (['knowledgemapgraphservice','knowledgemapblueprint','knowledgemapservice','knowledgemapcourseservice'] as $class) { $GLOBALS['objects'][$class]=new $class();if(method_exists($GLOBALS['objects'][$class],'init'))$GLOBALS['objects'][$class]->init(); }
$service=$GLOBALS['objects']['knowledgemapcourseservice'];$mapService=$GLOBALS['objects']['knowledgemapservice'];
function newMap($withContent=false) {
    global $maps,$nodes,$edges;
    $id=$maps->createMap(['title'=>'Ecology','ownerid'=>'author','scopetype'=>'personal','scopeid'=>'author','rootnodeid'=>'root']);
    $rows=[['root','course','Ecology'],['page','page','Forest layers'],['quiz','quiz','Check your knowledge']];
    if ($withContent) $rows[]=['text','content','<script>plain text</script>'];
    foreach ($rows as $i=>[$nodeId,$role,$title]) $nodes->addNode($id,['id'=>$id.$nodeId,'type'=>$role,'title'=>$title,'description'=>'','presentation'=>[],'order'=>$i]);
    $maps->updateMap($id,['rootnodeid'=>$id.'root']);
    foreach (array_slice($rows,1) as $i=>$row) $edges->addRelationship($id,['id'=>$id.'rel'.$i,'type'=>'contains','from'=>$id.($row[0]==='text'?'page':'root'),'to'=>$id.$row[0],'externalTarget'=>'','properties'=>[],'order'=>$i]);
    return $id;
}
$id=newMap();
$authors->create=false;rejects(fn()=>$service->generate($id,1,'ecology'),'forbidden');$authors->create=true;
$auth->manage=false;rejects(fn()=>$service->preview($id,1),'forbidden');$auth->manage=true;
rejects(fn()=>$service->generate($id,2,'ecology'),'stale');rejects(fn()=>$service->generate($id,1,'eco-logy'),'code');rejects(fn()=>$service->generate($id,1,str_repeat('a',33)),'code');
verify(!$contexts->getContext('ecology'),'Rejected requests wrote no course');
$result=$service->generate($id,1,'ecology');verify($result['contextCode']==='ecology','Course generated');
$context=$contexts->getContext('ecology');verify($context['status']==='Unpublished' && $context['access']==='Private','Safe defaults');
$state=$outline->snapshot('ecology');verify(count($state['chapters'])===1 && count($state['pages'])===2,'Page and quiz placement in generated container');
foreach ($state['pages'] as $page) verify(trim((string)$page['pagecontent'])==='','No implicit body');
$qs=$quizzes->snapshot('ecology');verify(count($qs['tests'])===1 && !$qs['questions'] && $qs['tests'][0]['status']==='inactive','Empty inactive quiz');
verify(count($state['blocks'])===2 && count($state['modules'])===2,'Course home and authoring modules');
$before=$state['chapters'][0]['id'];$service->generate($id,1,'ecology');$state=$outline->snapshot('ecology');verify($state['chapters'][0]['id']!==$before,'Untouched empty outline regenerated');
verify(count($contexts->selectPrepared('SELECT * FROM tbl_context WHERE contextcode=?',['ecology']))===1,'Retry does not duplicate course');
$authors->manage=false;rejects(fn()=>$service->generate($id,1,'ecology'),'forbidden');$authors->manage=true;
rejects(fn()=>$service->generate($id,1,'another'),'linked');
$pages->update('id',$state['pages'][0]['id'],['pagecontent'=>'<p>Authored content</p>']);
rejects(fn()=>$service->generate($id,1,'ecology'),'locked');
verify(str_contains((string)$pages->getRow('id',$state['pages'][0]['id'])['pagecontent'],'Authored'),'Authored content preserved');
$pages->update('id',$state['pages'][0]['id'],['pagecontent'=>'']);rejects(fn()=>$service->generate($id,1,'ecology'),'locked');
$contexts->delete('contextcode','ecology');rejects(fn()=>$service->generate($id,1,'ecology'),'exists');$service->generate($id,1,'replacement');verify((bool)$contexts->getContext('replacement'),'Deleted course permits replacement');
$contentId=newMap(true);$service->generate($contentId,1,'withcontent');$state=$outline->snapshot('withcontent');
verify(str_contains(json_encode($state['pages']),'&lt;script&gt;'),'Tagged content escaped');rejects(fn()=>$service->generate($contentId,1,'withcontent'),'locked');
$quizId=newMap();$service->generate($quizId,1,'quizguard');$qs=$quizzes->snapshot('quizguard');
$table->insert(['testid'=>$qs['tests'][0]['id'],'name'=>'Question','question'=>'Why?'],'tbl_test_questions');rejects(fn()=>$service->generate($quizId,1,'quizguard'),'locked');
$sectionMap=newMap();$doc=$mapService->document($sectionMap,'manage');
foreach ([['section','section','Ecosystems'],['chapter','chapter','Forests'],['description','description','Introduction <b>plain</b>']] as [$suffix,$role,$title])
    $doc['nodes'][]=['id'=>$sectionMap.$suffix,'type'=>$role,'title'=>$title,'description'=>'','presentation'=>[],'order'=>count($doc['nodes'])];
foreach ($doc['relationships'] as &$edge) $edge['from']=$sectionMap.'chapter';unset($edge);
foreach ([['root','section'],['section','chapter'],['section','description']] as $i=>[$from,$to])
    $doc['relationships'][]=['id'=>$sectionMap.'s'.$i,'type'=>'contains','from'=>$sectionMap.$from,'to'=>$sectionMap.$to,'order'=>$i];
$mapService->saveDocument($sectionMap,1,$doc);$service->generate($sectionMap,2,'sections');
$state=$outline->snapshot('sections');verify(count($state['sections'])===1 && $contexts->getContext('sections')['use_sections']==='1','Section layout enabled');
verify($state['placements'][0]['sectionid']===$state['sections'][0]['id'],'Chapter belongs to section');
verify(str_contains($state['sections'][0]['introduction'],'&lt;b&gt;'),'Explicit section description is escaped');
rejects(fn()=>$service->generate($sectionMap,2,'sections'),'locked');
$failId=newMap();$failing=new class extends contentauthoringservice { public function createNativePage(array $input){throw new RuntimeException('Injected page write failure');} };
inject($service,'pages',$failing);
try {$service->generate($failId,1,'rollback');throw new LogicException('Failure not injected');}catch(RuntimeException $e){verify($e->getMessage()==='Injected page write failure','Expected injected failure');}
verify(!$contexts->getContext('rollback') && !$outline->snapshot('rollback')['chapters'],'Partial course rolled back');
verify(!$table->selectPrepared('SELECT * FROM fixture_groups WHERE code=?',['rollback']),'Membership provisioning rolled back');
verify($service->linkedCode($failId)==='','No failed provenance');
inject($service,'pages',$pageService);$service->generate($failId,1,'rollback');
$before=$outline->snapshot('rollback');inject($service,'pages',$failing);
try{$service->generate($failId,1,'rollback');}catch(RuntimeException $e){verify($e->getMessage()==='Injected page write failure','Expected replacement failure');}
verify($before===$outline->snapshot('rollback'),'Failed regeneration preserves existing empty outline');
echo "PASS: real PDO course/page/quiz creation, safe defaults, empty regeneration, duplicate requests, explicit content escaping, permission failures, stale revision, quiz/page protection, deletion reset, atomic rollback and unchanged editor session\n";
