<?php
require __DIR__.'/public_entry_test.php';
$db=new class {
 public $row;
 function find($id){return $id==='stable-id'?$this->row:false;}
 function findBySlug($slug,$published){return false;}
};
$policy=new class {public $allow=false;function canManage(){return $this->allow;}};
$page->objects['dbsitepages']=$db;$page->objects['pagepolicy']=$policy;$page->objects['compositionservice']=new class {function fromPost($page){return [];}function renderCards($blocks){return '<audio controls></audio>';}};$page->init();
$page->params=['id'=>'stable-id'];
foreach(['published','draft','archived'] as $status)foreach([false,true] as $header)foreach([false,true] as $footer){
 $db->row=['id'=>'stable-id','slug'=>'renamed-page','title'=>'Bird','status'=>$status,'composition_json'=>json_encode(['show_header'=>$header,'show_footer'=>$footer])];$page->vars=[];http_response_code(200);$page->dispatch('view');
 if($status!=='published'){check(http_response_code()===404&&!isset($page->vars['sitepagesPage']),'Non-public ID target leaked');continue;}
 check($page->vars['sitepagesPage']['slug']==='renamed-page','Stable ID failed after rename');
 check(isset($page->vars['pageSuppressBanner'])===!$header,'Header preference ignored');check(isset($page->vars['pageSuppressToolbar'])===!$header,'Navigation preference ignored');check(isset($page->vars['suppressFooter'])===!$footer,'Footer preference ignored');
}
$db->row=['id'=>'stable-id','status'=>'published','body_html'=>'Audio'];$page->vars=[];$page->dispatch('view');check(!isset($page->vars['pageSuppressBanner'],$page->vars['suppressFooter']),'Legacy defaults changed');
echo "PASS stable page-ID route, renamed slug, draft/archive denial and independent display settings\n";
