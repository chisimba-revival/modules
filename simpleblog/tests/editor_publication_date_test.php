<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/publishingrenderer_class_inc.php';
$r=new class extends publishingrenderer {public function text($key){return 'Published';}};
foreach([
 [['post_status'=>'posted','published_at'=>'2026-05-21 09:13:45','datecreated'=>'2026-09-01 00:00:00'],'21 May 2026, 09:13 UTC'],
 [['post_status'=>'posted','datecreated'=>'2026-04-02 00:00:00'],'2 April 2026'],
 [['post_status'=>'draft','published_at'=>'2026-05-21 09:13:45'],'21 May 2026'],
 [['post_status'=>'draft','datecreated'=>'2026-04-02 00:00:00'],''],
 [['post_status'=>'posted','published_at'=>'2026-02-31 00:00:00'],'']
] as [$post,$expected]) { $html=$r->editorPublicationDate($post);if($expected===''?$html!=='':!str_contains($html,$expected))throw new RuntimeException('Publication date mismatch'); }
echo "PASS original publication date, legacy fallback, draft history and invalid date\n";
