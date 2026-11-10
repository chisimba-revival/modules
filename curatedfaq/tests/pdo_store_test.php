<?php
/** Disposable connection-local fixtures; never changes persistent FAQ records. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_FAQ_TEST')!=='1')exit(64);
chdir($argv[1]);$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$e=new engine();$s=$e->getObject('faqstore','curatedfaq');
function checkFaq($value,$label){if(!$value)throw new RuntimeException($label);}
$s->executePrepared('CREATE TEMPORARY TABLE tbl_curatedfaq_items (id VARCHAR(32) PRIMARY KEY, question VARCHAR(250), answer TEXT, status VARCHAR(20), display_order INT DEFAULT 0, revision INT DEFAULT 1) ENGINE=InnoDB');
$s->executePrepared('CREATE TEMPORARY TABLE tbl_classification_links (module_id VARCHAR(64), item_id VARCHAR(32), term_id VARCHAR(32)) ENGINE=InnoDB');
$s->executePrepared('CREATE TEMPORARY TABLE tbl_classification_terms (id VARCHAR(32), name VARCHAR(100)) ENGINE=InnoDB');
try {
 $published=['id'=>str_repeat('a',32),'question'=>"Who's eligible?",'answer'=>'Published answer','status'=>'published'];
 $draft=['id'=>str_repeat('b',32),'question'=>'Private draft','answer'=>'Draft answer','status'=>'draft'];
 $s->transaction(function()use($s,$published,$draft){$s->persist($published,false);$s->persist($draft,false);});
 $s->executePrepared('INSERT INTO tbl_classification_terms VALUES (?,?)',['category','General']);
 $s->executePrepared('INSERT INTO tbl_classification_links VALUES (?,?,?)',['curatedfaq',$published['id'],'category']);
 checkFaq(count($s->visibleCategories())===1,'Published categories visible');
 checkFaq(count($s->listing(false,'','category'))===1,'Category filter matches');
 checkFaq(count($s->listing())===1,'Draft excluded from public listing');
 checkFaq(count($s->listing(true))===2,'Manager sees both records');
 checkFaq(count($s->listing(false,"Who's"))===1,'Quoted search works');
 checkFaq($s->listing(false,"' OR 1=1 --")===[],'Search binds untrusted value');
 checkFaq($s->item("' OR 1=1 --")===null,'ID binds untrusted value');
 checkFaq($s->listing(false,'',"' OR 1=1 --")===[],'Category binds untrusted value');
 try {$s->transaction(function()use($s,$published){$s->persist(['id'=>$published['id'],'question'=>'Changed'],true);throw new DomainException('rollback-fixture');});}catch(DomainException $x){checkFaq($x->getMessage()==='rollback-fixture','Expected failure');}
 checkFaq($s->item($published['id'])['question']===$published['question'],'Failed transaction rolls back');
 $s->transaction(function()use($s,$published){checkFaq($s->item($published['id'],true)!==null,'Locking read');$s->persist(['id'=>$published['id'],'answer'=>'Updated'],true);});
 checkFaq($s->item($published['id'])['answer']==='Updated','Successful update commits');
 echo "PASS: PDO FAQ reads, writes, quoted inputs, draft privacy, locking and rollback\n";
}finally{foreach(['tbl_curatedfaq_items','tbl_classification_links','tbl_classification_terms'] as $table)$s->executePrepared('DROP TEMPORARY TABLE '.$table);}
