<?php
/** Catalogue migration must use the configured site, not the CLI operator URL. */
if(PHP_SAPI!=='cli')exit(64);
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {
 public array $mapping=[];
 public function getObject($name,$module){return $this;}
 public function getPages(){return [['id'=>'fixture-record','page'=>'7']];}
 public function getSiteRoot(){return 'https://example.invalid/ch/';}
 public function uri(...$args){throw new LogicException('CLI request URL must not be used');}
 public function migratePageKeys($mapping){$this->mapping=$mapping;}
}
require dirname(__DIR__).'/patches/installscripts_class_inc.php';
$hook=new slate_installscripts();$hook->postinstall('0.017');
$expected=[
 md5('https://example.invalid/ch/index.php?module=slate')=>md5('slate:default'),
 md5('https://example.invalid/ch/index.php?module=slate&action=view')=>md5('slate:default'),
 md5('https://example.invalid/ch/index.php?module=slate&page=7')=>md5('slate:page:fixture-record'),
 md5('https://example.invalid/ch/index.php?module=slate&action=view&page=7')=>md5('slate:page:fixture-record')
];
if($hook->mapping!==$expected)throw new RuntimeException('Incorrect canonical migration keys');
$hook->postinstall('0.017');if($hook->mapping!==$expected)throw new RuntimeException('Repeat mapping changed');
echo "PASS canonical Slate migration keys are independent of CLI request URL and repeatable\n";
