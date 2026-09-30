<?php
if(PHP_SAPI!=='cli'||getenv('MCQBANK_LOCAL_TEST')!=='1')exit(64);
// The caller must explicitly select a disposable runtime. Never use a live installation.
$runtime=getenv('MCQBANK_TEST_RUNTIME');if(!$runtime||!is_file($runtime.'/.mcq-bank-disposable'))exit(64);
chdir($runtime);$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost:8097';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$e=new engine();
$f=json_decode(file_get_contents('/tmp/mcq-bank-fixture.json'),true);if(!preg_match('/^bankqa[a-f0-9]{6}$/D',$f['tag']??''))exit(64);
$user=$e->getObject('user','security');$session=new NativeSessionService($user,static fn()=>true);$session->establish($f['ids']['admin'],['username'=>$f['users']['admin']]);
$ctx=$e->getObject('dbcontext','context');$groups=$e->getObject('groupservice','groupadmin');
foreach(['a','b','c'] as $suffix){$code=$f['tag'].$suffix;$ctx->createContext($code,'Bank QA '.$suffix,'Published','Private','',false,0);$f['courses'][$suffix]=$code;
foreach(['teacher'=>'Lecturers','other'=>'Lecturers','student'=>'Students'] as $role=>$group){if($role==='teacher'&&$suffix==='c')continue;$groups->ensureMembership($groups->groupIdForName($code.'^'.$group),$e->getObject('identityservice','security')->ensurePermissionIdentity($f['ids'][$role]));}
$e->getObject('dbcontextmodules','context')->addModule($code,'mcqtests');
}
$chapter=$e->getObject('db_contextcontent_chapters','contextcontent')->addChapter('', 'Chapter 1 — Grasses', 'Synthetic botany chapter');
$e->getObject('db_contextcontent_contextchapter','contextcontent')->addChapterToContext($chapter,$f['courses']['a'],'Y');
$tests=$e->getObject('dbtestadmin','mcqtests');
foreach(['source'=>'a','target'=>'b','outside'=>'c'] as $name=>$suffix){$f['tests'][$name]=$tests->addTest(['context'=>$f['courses'][$suffix],'chapter'=>$name==='source'?$chapter:'','userid'=>$f['ids']['admin'],'name'=>'Bank QA '.$name,'description'=>'Synthetic fixture','status'=>'inactive','totalmark'=>0,'percentage'=>0,'duration'=>0,'timed'=>0,'testtype'=>'Formative','qsequence'=>'Sequential','asequence'=>'Scrambled','comlab'=>'','updated'=>date('Y-m-d H:i:s'),'coursePermissions'=>'Private']);}
$q=['stem'=>'Which part of a grass absorbs water?','options'=>['Roots','Leaves','Flowers','Seeds'],'correctIndex'=>0,'sourceBasis'=>'Roots absorb water.','rationale'=>'Roots take up water from soil.'];
$e->getObject('mcqaigenerator','mcqtests')->insertQuestions($f['tests']['source'],[$q]);
$exam=$e->getObject('examstore','questiongenerator')->createExam($f['ids']['admin'],'Bank QA exam',$e->getObject('examservice','questiongenerator')->emptyContent());
$q['included']=true;$q2=$q;$q2['stem']='Which part of a grass makes seeds?';$q2['correctIndex']=2;$q2['sourceBasis']='Flowers make seeds.';
$store=$e->getObject('workshopstore','questiongenerator');$f['set']=$store->createSet($f['ids']['admin'],'Chapter 1 — Grasses',str_repeat('Roots absorb water. Flowers make seeds. ',5),[$q,$q2],2,$exam,'mcq');$row=$store->one($f['set']);$store->saveSet($row,$row['title'],[$q,$q2],true);
$e->getObject('dbsysconfig','sysconfig')->changeParam('MOD_SECURITY_HTTPS','security','0');
file_put_contents('/tmp/mcq-bank-fixture.json',json_encode($f));echo "Synthetic course, test and reviewed generator fixtures ready.\n";
