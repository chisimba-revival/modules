<?php
/** Opt-in disposable MariaDB upgrade test. Creates and drops only its random shop_test_* database. */
if (PHP_SAPI!=='cli' || !getenv('SHOP_UPGRADE_TEST_HOST')) exit(64);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$connection=new mysqli(getenv('SHOP_UPGRADE_TEST_HOST'),getenv('SHOP_UPGRADE_TEST_USER')?:'root',getenv('SHOP_UPGRADE_TEST_PASSWORD')?:'');
$pdo=new class($connection){function __construct(public $db){}function exec($sql){return $this->db->query($sql);}function query($sql){return $this->db->query($sql);}function quote($v){return "'".$this->db->real_escape_string($v)."'";}};
$name='shop_test_'.bin2hex(random_bytes(6));$pdo->exec('CREATE DATABASE '.$name);$pdo->exec('USE '.$name);
$GLOBALS['kewl_entry_point_run']=true;define('MDB2_FETCHMODE_ASSOC',2);
class PEAR{static function isError($v){return false;}}
class ChisimbaObject{public $objEngine;function getObject($n,$m=''){return $GLOBALS['services'][$n];}}
class dbTable extends ChisimbaObject{}
class ShopUpgradeDb{
 public $manager;function __construct(public $pdo){$this->manager=$this;}
 function loadModule($name){}
 function queryAll($sql,$types=null,$mode=null){return array_map(fn($row)=>array_change_key_case($row,CASE_LOWER),$this->pdo->query($sql)->fetch_all(MYSQLI_ASSOC));}
 function exec($sql){return $this->pdo->exec($sql);}
 function quote($v,$type='text'){return $this->pdo->quote($v);}
 function createConstraint($table,$name,$def){return $this->exec('ALTER TABLE '.$table.' ADD UNIQUE KEY '.$name.' ('.implode(',',array_keys($def['fields'])).')');}
 function createIndex($table,$name,$def){return $this->exec('CREATE INDEX '.$name.' ON '.$table.' ('.implode(',',array_keys($def['fields'])).')');}
}
require dirname(__DIR__).'/classes/shopstore_class_inc.php';
require dirname(__DIR__).'/patches/installscripts_class_inc.php';
$db=new ShopUpgradeDb($pdo);$engine=new class($db){function __construct(public $db){}function getDbObj(){return $this->db;}};
$store=new shopstore;$store->objEngine=$engine;
$GLOBALS['services']=['shopstore'=>$store,'permissionservice'=>new class{function ensureArea($a,$b){return 'shop';}function ensureRight($a,$b){return true;}},'dbsysconfig'=>new class{function getValue($a,$b){return str_repeat('a',64);}}];
$hook=new shop_installscripts;$hook->objEngine=$engine;
try {
 foreach ([true,false] as $legacy) {
  foreach (glob(dirname(__DIR__).'/sql/tbl_shop_*.sql') as $schema) {
   require $schema;$pdo->exec('DROP TABLE IF EXISTS '.$tablename);$cols=[];
   foreach($fields as $field=>$def) {
    if($legacy && in_array($field,['product_type','product_options','user_id','display_order'],true))continue;
    $type=match($def['type']){'integer'=>'INT','clob'=>'LONGTEXT',default=>'VARCHAR('.$def['length'].')'};
    $cols[]=$field.' '.$type.(!empty($def['notnull'])?' NOT NULL':' NULL').(isset($def['default'])?' DEFAULT '.$pdo->quote($def['default']):'');
   }
   $pdo->exec('CREATE TABLE '.$tablename.' ('.implode(',',$cols).') ENGINE=InnoDB');
  }
  $pdo->exec("INSERT INTO tbl_shop_books (id,title,isbn,description,image_url,price_minor,stock,status,revision) VALUES ('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa','Original book','','','',15000,12,'published',4)");
  $hook->postinstall();$hook->postinstall();
  $row=$store->one('books',str_repeat('a',32));
  if((int)$row['display_order']!==0||$row['product_type']!=='physical'||$row['product_options']!==null||(int)$row['stock']!==12||(int)$row['price_minor']!==15000||(int)$row['revision']!==4)throw new RuntimeException('Legacy book changed');
  $columns=array_column($db->queryAll('SHOW COLUMNS FROM tbl_shop_orders'),'field');
  if(!in_array('user_id',$columns,true))throw new RuntimeException('Account column missing');
  $pdo->exec("UPDATE tbl_shop_books SET product_type='virtual',product_options='{\"kind\":\"contribution\"}'");
  $hook->postinstall();if($store->one('books',str_repeat('a',32))['product_type']!=='virtual')throw new RuntimeException('Upgrade overwrote product type');
  $pdo->exec("INSERT INTO tbl_shop_books (id,title,isbn,description,image_url,price_minor,stock,status,revision,display_order) VALUES ('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb','Earlier','','','',10000,1,'published',1,10)");
  $pdo->exec("UPDATE tbl_shop_books SET display_order=20 WHERE id='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'");
  $hook->postinstall();$ordered=$store->rows('books');
  if($ordered[0]['title']!=='Earlier'||(int)$ordered[1]['display_order']!==20)throw new RuntimeException('Explicit order ignored or reset');
  echo 'PASS: '.($legacy?'legacy upgrade':'fresh installation').", repeatable hooks, physical defaults, preserved stock/price/revision and virtual metadata.\n";
 }
} finally {$pdo->exec('DROP DATABASE '.$name);}
