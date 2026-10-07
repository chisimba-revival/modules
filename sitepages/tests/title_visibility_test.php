<?php
/** Page display preferences preserve publishing state, metadata and authored blocks. */
class dbTable {}
require dirname(__DIR__).'/classes/dbsitepages_class_inc.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
class TitleHost {
 public $metadataTitle;
 public function getObject($name,$module){return match($name){
  'pagemetadata'=>new class($this){public function __construct(private $host){} public function build($title,$body,$route){$this->host->metadataTitle=$title;return [];}},
  'language'=>new class{public function languageText($key,$module){return 'Edit';}},
  'iconservice'=>new class{public function render($name,$options){return '<svg></svg>';}}
 };}
 public function uri($params,$module){return '/index.php?'.http_build_query($params);}
 public function render($row,$editor){$sitepagesPage=$row;$sitepagesCanEdit=$editor;ob_start();include dirname(__DIR__).'/templates/content/view_tpl.php';return ob_get_clean();}
}
$base=['id'=>'fixture','title'=>'Page title','slug'=>'fixture','status'=>'published','body_html'=>'<h2>Block title</h2><p>Body</p>'];
$host=new TitleHost;
foreach([null,true,false] as $show){
 $row=$base;if($show!==null)$row['composition_json']=json_encode(['version'=>1,'show_title'=>$show,'blocks'=>[]]);
 foreach([false,true] as $editor){
  $html=$host->render($row,$editor);
  check(str_contains($html,'<h1>Page title</h1>')===($show!==false),'Visible title preference');
  check(str_contains($html,'Block title'),'Block heading retained');
  check(str_contains($html,'chisimba-icon-button')===$editor,'Editor shortcut permission');
  check($host->metadataTitle==='Page title','Browser metadata title retained');
  if($show===false&&!$editor)check(!str_contains($html,'<header'),'Hidden heading leaves no empty header');
 }
}
check(!dbsitepages::showTitle(['show_title'=>false]),'Unchecked draft retained');
check(dbsitepages::showTitle([]),'New page default');
check(dbsitepages::version($base)!==dbsitepages::version($base+['composition_json'=>'{"show_title":false}']),'Visibility participates in stale-save detection');
echo "PASS: title visibility, legacy/new defaults, block headings, metadata, edit permissions and revision detection\n";
