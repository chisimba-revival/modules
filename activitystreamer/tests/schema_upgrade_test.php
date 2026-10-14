<?php
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {
 public $admin,$columns=[['kind'=>'int','size'=>null]];
 function getObject($n,$m){return $this->admin;}
 function selectPrepared($sql,$params=[]){return $this->columns;}
}
require __DIR__.'/../patches/installscripts_class_inc.php';
$admin=new class {public $tables=['tbl_activity'],$calls=[];function listDbTables(){return $this->tables;}function alterTable($table,$changes,$check){$this->calls[]=[$table,$changes,$check];return true;}};
$patch=new activitystreamer_installscripts();$patch->admin=$admin;$patch->preinstall();
if(count($admin->calls)!==2||$admin->calls[0][2]!==true||$admin->calls[1][1]!==['change'=>['createdby'=>['type'=>'text','length'=>50]]])throw new RuntimeException('guarded widening');
$patch->columns=[['kind'=>'varchar','size'=>50]];$patch->preinstall();if(count($admin->calls)!==2)throw new RuntimeException('repeat alteration');
$admin->tables=[];$patch->preinstall();if(count($admin->calls)!==2)throw new RuntimeException('fresh install alteration');
echo "PASS: legacy author widening is preflighted, repeatable and skipped for fresh tables.\n";
