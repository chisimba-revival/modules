<?php
/** Focused archive persistence and rendering contracts; no database or network required. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject{}
class dbTable {
 public $rows=[];
 public function getRow($field,$value){foreach($this->rows as $r)if($r[$field]===$value)return $r;return false;}
 public function insert($r){$this->rows[]=$r;return $r['id'];}
}
require __DIR__.'/../classes/webinarstore_class_inc.php';
require __DIR__.'/../classes/webinarrenderer_class_inc.php';
function expect($value,$message){if(!$value)throw new RuntimeException($message);}
$s=new webinarstore();$record=['id'=>str_repeat('a',32),'source_key'=>'test|webinar|1','source_hash'=>'same','status'=>'published'];
expect($s->importRecord($record)==='created','First import');expect($s->importRecord($record)==='unchanged','Repeat import');expect(count($s->rows)===1,'No duplicate');
$record['source_hash']='changed';$conflict=false;try{$s->importRecord($record);}catch(RuntimeException $e){$conflict=true;}expect($conflict,'Changed source needs review');
expect($s->one('invalid')===null,'Invalid identity hidden');expect($s->one(str_repeat('a',32))!==null,'Published identity available');$s->rows[0]['status']='draft';expect($s->one(str_repeat('a',32))===null,'Draft hidden');
expect(webinarrenderer::escape('<script>"')==='&lt;script&gt;&quot;','Escape markup');
$r=new webinarrenderer();$image=$r->image('/image.webp','Sample');expect(str_contains($image,'height:auto')&&str_contains($image,'object-fit:contain'),'Full image proportions');
echo "Archive import, conflict, draft and image contracts passed\n";
