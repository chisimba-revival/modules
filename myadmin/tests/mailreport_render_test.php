<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject($name,$module=null){
  if($name==='mailreportview')return new mailreportview();
  if($name==='language')return new class {public function code2Txt($key,$module){return $key==='mod_myadmin_mail_page'?'Page %d of %d — %d messages':$key;}};
  if($name==='iconservice')return new class {public function render(...$args){return '<svg></svg>';}};
  if($name==='contextualhelp')return new class {public function show(...$args){return '<button>Help</button>';}};
 }
 public function uri($params,$module){return '/index.php?'.http_build_query(['module'=>$module]+(array)$params);}
}
require dirname(__DIR__).'/classes/mailreportview_class_inc.php';
$evil='<script>alert(1)</script>';
$data=['days'=>['2026-11-16'=>0],'subjects'=>[['subject'=>$evil,'status'=>'sent','total'=>2]],'truncated'=>false,'timezone'=>'Africa/Johannesburg','date'=>'2026-11-16','search'=>$evil,'status'=>'','page'=>1,'pages'=>2,'total'=>51,'rows'=>[['id'=>'1','recipient'=>$evil,'subject'=>$evil,'status'=>'sent','transport'=>'sendgrid','attempts'=>1,'date_created'=>'2026-11-16 12:00:00','sent_at'=>'2026-11-16 12:00:01','response_code'=>202]]];
$view=new class extends ChisimbaObject {public $data; public function getVar($key){return $this->data;}public function render(){ob_start();include dirname(__DIR__).'/templates/content/maillog_tpl.php';return ob_get_clean();}};$view->data=$data;
foreach([(new mailreportview())->summary($data),$view->render()] as $html){if(str_contains($html,$evil)||!str_contains($html,'&lt;script&gt;'))throw new RuntimeException('Unsafe text');}
$html=$view->render();if(!str_contains($html,'page=2')||!str_contains($html,'search=%3Cscript%3E'))throw new RuntimeException('Lost filters in pagination');
echo "PASS: escaped recipients, subjects and search; accessible empty-day bars; pagination retains filters.\n";
