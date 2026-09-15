<?php
$GLOBALS['kewl_entry_point_run']=true;
require dirname(__DIR__).'/classes/publishingimportrecord_class_inc.php';
class ChisimbaObject{}
require dirname(__DIR__).'/classes/publishingrenderer_class_inc.php';
$r=['id'=>'a','post_title'=>'Old article','post_content'=>'<p>Original</p>','post_status'=>'posted','datecreated'=>'2020-01-01 17:00:00','published_at'=>'2020-01-01 17:00:00','userid'=>'editor','author_credit'=>'Original author','source_key'=>'source|1','source_hash'=>hash('sha256','source')];$r['import_hash']=publishingimportrecord::fingerprint($r);
if(publishingimportrecord::compare(null,$r)!=='created'||publishingimportrecord::compare($r,$r)!=='unchanged')throw new RuntimeException('Import identity');
foreach(['post_title','post_status','published_at','author_credit','source_hash'] as $key){$changed=$r;$changed[$key]='edited';try{publishingimportrecord::compare($changed,$r);throw new LogicException('Overwrite accepted');}catch(RuntimeException $expected){}}
if(publishingrenderer::authorCredit($r,'Editor')!=='Original author'||publishingrenderer::authorCredit([],'Editor')!=='Editor')throw new RuntimeException('Wrong attribution');
echo "PASS: repeated imports, editorial/source conflict protection, historical dates and independent author credit\n";
