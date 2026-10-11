<?php
/** Retired entry points must refuse --apply before any application bootstrap. */
$root=dirname(__DIR__);
$paths=['audience/scripts/import-icegram-users.php','webinar/scripts/import-full-archive.php','webinar/scripts/import-youtube-channel.php','webinar/scripts/import-icegram-upcoming.php','webinar/scripts/preserve-icegram-draft.php','simpleblog/scripts/import-wordpress-posts.php','contentblocks/scripts/configure-publication-imports.php'];
foreach($paths as $path){
 $proc=proc_open([PHP_BINARY,'-n','-d','auto_prepend_file=',$root.'/'.$path,'--apply'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,sys_get_temp_dir());
 fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($proc);
 if($exit!==64||$out!==''||!str_starts_with($err,'Retired LearnTheBirds staging migration.')||!str_contains($err,'No changes made.'))throw new RuntimeException('Retirement guard failed: '.$path);
}
echo "PASS: seven retired operators refuse apply without application bootstrap\n";
