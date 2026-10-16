<?php
if(PHP_SAPI!=='cli')exit(64);
foreach([['anonymous','POST','valid',403,0],['member','POST','valid',403,0],['revoked-admin','POST','valid',403,0],['admin','GET','valid',405,0],['admin','POST','expired',403,0],['admin','POST','valid',200,1]] as [$role,$method,$token,$status,$writes]) {
 $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/controller_probe.php').' '.implode(' ',array_map('escapeshellarg',[$role,$method,$token]));
 exec($cmd.' 2>&1',$lines,$exit);$out=implode("\n",$lines);$lines=[];
 if($exit!==0||!str_contains($out,'RESULT:'))throw new RuntimeException($out);
 $result=json_decode(substr($out,strrpos($out,'RESULT:')+7),true);
 if($result!==['status'=>$status,'writes'=>$writes])throw new RuntimeException($role.' '.$method.' '.$out);
}
echo "PASS actual controller: rejected requests write nothing, valid admin POST delegates once\n";
