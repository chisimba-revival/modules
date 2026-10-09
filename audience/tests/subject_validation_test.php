<?php
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {public function getObject($n,$m){return new class {public function guard(){}};}}
require $argv[1]??__DIR__.'/../classes/audiencecampaigns_class_inc.php';
foreach (['', '   '] as $subject) {
 try {(new audiencecampaigns)->save('', ['subject'=>$subject,'body'=>'Retained draft']);throw new RuntimeException('Accepted blank subject');}
 catch (DomainException $e) {if($e->getMessage()!=='subject_required')throw $e;}
}
echo "PASS server rejects blank and whitespace subjects with specific notice before database work\n";
