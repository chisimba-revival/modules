<?php
if(PHP_SAPI!=='cli')exit(64);
foreach(['deleteuserperm','addedituserpermissions'] as $action) {
 foreach([['anonymous','POST','valid',403],['cms-author','POST','valid',403],['revoked-admin','POST','valid',403],['admin','GET','valid',405],['admin','POST','expired',403],['admin','POST','valid',200]] as [$role,$method,$token,$status]) {
  $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/permission_controller_probe.php').' '.implode(' ',array_map('escapeshellarg',[$role,$method,$token,$action]));
  exec($cmd.' 2>&1',$lines,$exit);$out=implode("\n",$lines);$lines=[];
  if($exit!==0||!str_contains($out,'RESULT:'))throw new RuntimeException($out);
  $actual=json_decode(substr($out,strrpos($out,'RESULT:')+7),true);
  $writes=$status===200 ? ($action==='deleteuserperm'?[['delete','record-one']]:[['edit','record-one','user-two',true]]) : [];
  if($actual!==['status'=>$status,'writes'=>$writes])throw new RuntimeException($out);
 }
}
echo "PASS actual CMS controller: denied writes zero; admin save/delete correct arguments
";
