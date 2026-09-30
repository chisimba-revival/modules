<?php
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){} public function getObject($name,$module=null){return $GLOBALS['objects'][$name];}}
require dirname(__DIR__).'/classes/membershipservice_class_inc.php';
function verify($ok,$message){if(!$ok)throw new RuntimeException($message);}
$config=new class {public $value; public function getValue($key,$module){return $this->value;}};
$ledger=new class {public $rows=[];public function activeForUser(...$args){return $this->rows;}};
$GLOBALS['objects']=['dbsysconfig'=>$config,'entitlementservice'=>$ledger,'userservice'=>new stdClass(),'accounteventservice'=>new stdClass()];
$config->value=json_encode([
 ['code'=>'registered','rank'=>0,'enabled'=>true,'baseline'=>true,'label'=>'Registered'],
 ['code'=>'supporter','rank'=>10,'enabled'=>true,'label'=>'Supporter'],
 ['code'=>'patron','rank'=>20,'enabled'=>false,'label'=>'Patron']]);
$service=new membershipservice();$service->init();
verify($service->effectiveTier('person')==='registered','Configured baseline');
verify($service->tierIncludes('patron','supporter'),'Higher includes lower even when disabled for selection');
verify(!$service->tierIncludes('supporter','patron'),'Lower cannot access higher');
verify(!isset($service->tiers(true)['patron']),'Disabled hidden');
verify(!$service->tierIncludes('patron','unknown'),'Unknown fails closed');
$ledger->rows=[['entitlement_type'=>'membership_tier','resource_type'=>'membership_tier','resource_id'=>'supporter']];
verify($service->effectiveTier('person')==='supporter','Canonical active grants used');
$ledger->rows=[];verify($service->effectiveTier('person')==='registered','Expired/revoked grants disappear');
$config->value='broken';verify(!$service->tierIncludes('patron','supporter'),'Invalid config fails closed');
echo "PASS: arbitrary tier data, baseline, inheritance, disabled choices, unknown and revoked access\n";
