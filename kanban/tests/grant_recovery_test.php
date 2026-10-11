<?php
/** Sharing must not report success or discard existing grants on a partial write. */
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {
    public $rows=['existing']; public $snapshot; public $fail=false; public $objEngine;
    public function transactionWithSavepoint(callable $work){$snapshot=$this->rows;try{return $work();}catch(Throwable $e){$this->rows=$snapshot;throw $e;}}
    public function executePrepared($sql,$params=[]){if(str_starts_with($sql,'DELETE'))$this->rows=[];return 1;}
    public function insert($row){if($this->fail)return false;$this->rows[]=$row;return 'id';}
}
require dirname(__DIR__).'/classes/dbkanbanaccess_class_inc.php';
$store=new dbkanbanaccess();$store->objEngine=new class {public function getDbObj(){return new stdClass();}};
$store->fail=true;
if($store->replaceUserGrants('board',['new-user'=>'edit'],'actor')!==false||$store->rows!==['existing'])throw new RuntimeException('Failed sharing change lost grants');
$store->fail=false;
if(!$store->replaceUserGrants('board',['new-user'=>'edit'],'actor')||count($store->rows)!==1||$store->rows[0]['principalid']!=='new-user')throw new RuntimeException('Successful sharing change failed');
echo "PASS: failed sharing write rolls back; successful replacement commits\n";
