<?php
/** Idempotent upgrade; preserves legacy HTML and migrates existing tag associations. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
$app=$argv[1]??dirname(__DIR__,3).'/framework/app';chdir($app);
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;require_once 'classes/core/engine_class_inc.php';$engine=new engine();
$engine->getObject('classificationservice','classification');
$posts=$engine->getObject('publishingstore','simpleblog');$store=$engine->getObject('classificationstore','classification');
$columns=array_column($posts->query('SHOW COLUMNS FROM tbl_simpleblog_posts'),'field');
foreach(['featured_image'=>'LONGTEXT NULL','featured_alt'=>'LONGTEXT NULL','composition_json'=>'LONGTEXT NULL','legacy_content_html'=>'LONGTEXT NULL','published_at'=>'DATETIME NULL'] as $column=>$type){
 if(!in_array($column,$columns,true)&&$posts->query('ALTER TABLE tbl_simpleblog_posts ADD COLUMN '.$column.' '.$type)===false)throw new RuntimeException('Schema upgrade failed');
}
$posts->begin();$count=0;
try {
 if($posts->query("UPDATE tbl_simpleblog_posts SET published_at=datecreated WHERE post_status='posted' AND published_at IS NULL")===false)throw new RuntimeException('Date migration failed');
 foreach($posts->query('SELECT * FROM tbl_simpleblog_posts ORDER BY id') as $post){
  // Composed posts already use the shared service; never overwrite their current associations.
  if(!empty($post['composition_json']))continue;
  $v=classificationservice::vocabulary($post['post_type'],$post['blogid'],'tag');
  $store->locked($v,function()use($store,$v,$post){
   $ids=[];
   foreach(explode(',',(string)$post['post_tags']) as $name){
    $name=trim($name);if($name==='')continue;
    if(mb_strlen($name)>150||preg_match('/[\x00-\x1f<>]/',$name))throw new RuntimeException('Review unsupported legacy tag in post '.$post['id']);
    $slug=trim(preg_replace('/[^\pL\pN_-]+/u','-',mb_strtolower($name)),'-');
    if($slug==='')throw new RuntimeException('Review empty legacy tag slug in post '.$post['id']);
    $found=null;
    foreach($store->terms($v['id']) as $term){
     if(mb_strtolower($term['name'])===mb_strtolower($name))$found=$term;
     elseif($term['slug']===$slug)throw new RuntimeException('Review legacy tag slug collision in post '.$post['id']);
    }
    if(!$found){$found=['id'=>bin2hex(random_bytes(16)),'vocabulary_id'=>$v['id'],'name'=>$name,'slug'=>$slug,'parent_id'=>''];$store->saveTerm($found);}
    $ids[]=$found['id'];
   }
   $store->replaceLinks($v['id'],'simpleblog',$post['id'],array_values(array_unique($ids)));
  });++$count;
 }
 $posts->commit();echo "Composition columns ready; $count legacy posts classified. Original HTML retained.\n";
}catch(Throwable $e){$posts->rollback();throw $e;}
