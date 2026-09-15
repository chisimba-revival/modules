<?php
/** Reviewed staging-only WordPress import; records/terms commit together. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com')throw new RuntimeException('Migration staging only');
$in=json_decode(file_get_contents('/tmp/ltb-posts/source.json'),true,512,JSON_THROW_ON_ERROR);if($in['source']!=='https://learnthebirds.com'||$in['issues']||$in['permalink_structure']!=='/%postname%/')throw new RuntimeException('Source requires review');
$apply=in_array('--apply',$argv,true);$db=$e->getDbObj();$user=$e->getObject('user','security');$owner=$user->getUserId('derek');if(!$owner)throw new RuntimeException('Missing destination editorial owner');
foreach($in['posts'] as $p)if(!empty($p['protected'])||($p['post_status']==='draft'&&trim($p['post_content'])!==''))throw new RuntimeException('Private source content requires separate media review');
require 'packages/contentblocks/scripts/migration-media.php';$urls=migrationMedia($e,$in['attachments'],'/tmp/ltb-posts/media',$apply);$replace=[];foreach($in['media_urls'] as $url=>$key)$replace[$url]=$urls[$key];
function identity($id){return substr(hash('sha256','learnthebirds.com|post|'.$id),0,32);}
foreach($in['posts'] as $p){$url=html_entity_decode($e->uri(['action'=>'view','id'=>identity($p['ID'])],'simpleblog'),ENT_QUOTES,'UTF-8');foreach(['https://learnthebirds.com/','http://learnthebirds.com/','https://www.learnthebirds.com/'] as $root){$replace[$root.$p['post_name'].'/']=$url;$replace[$root.'?p='.$p['ID']]=$url;}}
$archive='/tmp/webinar-full/trial-export.json';if(is_file($archive))foreach(json_decode(file_get_contents($archive),true)['events'] as $w)$replace['https://learnthebirds.com/event/'.$w['post_name'].'/']=html_entity_decode($e->uri(['action'=>'view','id'=>substr(hash('sha256','learnthebirds.com|webinar|'.$w['ID']),0,32)],'webinar'),ENT_QUOTES,'UTF-8');
$builder=$e->getObject('compositionservice','contentblocks');$converter=$e->getObject('wordpresspostconverter','simpleblog');$store=$e->getObject('publishingstore','simpleblog');$e->loadClass('publishingimportrecord','simpleblog');$pending=[];$counts=[];
foreach($in['posts'] as $p){
 $blocks=$converter->convert($p,$replace);$title=trim(html_entity_decode(strip_tags($p['post_title']),ENT_QUOTES|ENT_HTML5,'UTF-8'));if($title===''||mb_strlen($title)>250)throw new RuntimeException('Invalid post title');
 $date=$p['post_date_gmt'];if(!$date||str_starts_with($date,'0000'))$date=(new DateTimeImmutable($p['post_date'],new DateTimeZone($in['timezone']?:'Africa/Johannesburg')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
 $tags=[];foreach($p['terms'] as $id)if($in['terms'][$id]['taxonomy']==='post_tag')$tags[]=html_entity_decode($in['terms'][$id]['name'],ENT_QUOTES|ENT_HTML5,'UTF-8');
 $sourceHash=hash('sha256',json_encode($p,JSON_THROW_ON_ERROR));$row=['id'=>identity($p['ID']),'blogid'=>'site','post_type'=>'site','userid'=>$owner,'modifierid'=>$owner,'datecreated'=>$date,'published_at'=>$p['post_status']==='publish'?$date:null,'datemodified'=>$p['post_modified_gmt']?:$date,'post_title'=>$title,'post_status'=>$p['post_status']==='publish'?'posted':'draft','post_content'=>$builder->render($blocks),'post_tags'=>implode(', ',$tags),'featured_image'=>$p['featured']?$urls[$p['featured']]:'','featured_alt'=>$p['featured_alt']??'','composition_json'=>json_encode(['version'=>1,'revision'=>substr($sourceHash,0,24),'blocks'=>$blocks],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'legacy_content_html'=>$p['post_content'],'author_credit'=>$p['author_name'],'source_key'=>'learnthebirds.com|post|'.$p['ID'],'source_url'=>'https://learnthebirds.com/'.$p['post_name'].'/','source_hash'=>$sourceHash];
 $row['import_hash']=publishingimportrecord::fingerprint($row);$pending[]=[$row,$p];$counts[$row['post_status']]=($counts[$row['post_status']]??0)+1;
}
if(!$apply){echo json_encode(['dry_run'=>$counts,'verified_media'=>count($urls),'blocks'=>array_sum(array_map(fn($x)=>count(json_decode($x[0]['composition_json'],true)['blocks']),$pending))])."\n";exit;}
$unique=[];foreach($store->query('SHOW INDEX FROM tbl_simpleblog_posts') as $index)if(!$index['non_unique'])$unique[$index['key_name']]=1;if(!isset($unique['publication_identity_unique'],$unique['simpleblog_source_unique']))throw new RuntimeException('Run configure-publication-imports.php first');
// Trusted CLI import uses classification's storage transaction boundary; it grants no web rights.
$classification=$e->getObject('classificationstore','classification');$e->loadClass('classificationservice','classification');$termIds=[];$termPlan=[];
foreach($in['terms'] as $sourceId=>$term){$kind=$term['taxonomy']==='category'?'category':'tag';$v=classificationservice::vocabulary('site','site',$kind);$name=trim(html_entity_decode($term['name'],ENT_QUOTES|ENT_HTML5,'UTF-8'));$slug=rawurldecode($term['slug']);if($name===''||mb_strlen($name)>150||preg_match('/[<>\x00-\x1f]/u',$name)||!preg_match('/^[\pL\pN_-]+$/uD',$slug))throw new RuntimeException('Invalid taxonomy term');$existing=null;foreach($classification->terms($v['id']) as $old)if(mb_strtolower($old['name'])===mb_strtolower($name)||$old['slug']===$slug){if($old['name']!==$name||$old['slug']!==$slug)throw new RuntimeException('Taxonomy conflict');$existing=$old;break;}$termIds[$sourceId]=$existing['id']??substr(hash('sha256','learnthebirds.com|term|'.$sourceId),0,32);$termPlan[$sourceId]=[$v,['id'=>$termIds[$sourceId],'vocabulary_id'=>$v['id'],'name'=>$name,'slug'=>$slug,'parent_id'=>''],$term,$existing];}
foreach($termPlan as &$plan){$plan[1]['parent_id']=$plan[2]['parent']?$termIds[$plan[2]['parent']]:'';if($plan[3]&&$plan[3]['parent_id']!==$plan[1]['parent_id'])throw new RuntimeException('Taxonomy parent changed');}unset($plan);
$begin=$db->exec('START TRANSACTION');if(PEAR::isError($begin))throw new RuntimeException('Cannot start article transaction');$result=[];
try{
 foreach($pending as [$row,$p])$result[$row['id']]=publishingimportrecord::compare($store->lockPost($row['id']),$row);
 foreach($termPlan as [$v,$term])$classification->locked($v,fn()=>$classification->saveTerm($term));
 foreach($pending as [$row,$p]){
  if($result[$row['id']]==='created'){
   $quoted=array_map(fn($value)=>$db->quote($value),array_values($row));
   $insert=$db->exec('INSERT INTO tbl_simpleblog_posts ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$quoted).')');
   if(PEAR::isError($insert))throw new RuntimeException('Post insert failed: '.$insert->getMessage());
   $saved=$store->lockPost($row['id']);if(!$saved||!hash_equals($row['import_hash'],publishingimportrecord::fingerprint($saved)))throw new RuntimeException('Stored article differs from reviewed content');
  }
  foreach(['tag','category'] as $kind){$v=classificationservice::vocabulary('site','site',$kind);$assigned=[];foreach($p['terms'] as $tid)if(($in['terms'][$tid]['taxonomy']==='category'?'category':'tag')===$kind)$assigned[]=$termIds[$tid];
   if($result[$row['id']]==='unchanged'){$existing=array_column($classification->itemTerms($v['id'],'simpleblog',$row['id']),'id');sort($existing);$compare=$assigned;sort($compare);if($existing!==$compare)throw new RuntimeException('Article classification edited');}
   else $classification->locked($v,fn()=>$classification->replaceLinks($v['id'],'simpleblog',$row['id'],$assigned));
  }
 }
 $committed=$db->exec('COMMIT');if(PEAR::isError($committed))throw new RuntimeException('Article commit failed');
}catch(Throwable $error){$store->rollback();throw $error;}
echo json_encode(['records'=>array_count_values($result),'statuses'=>$counts,'terms'=>count($termPlan)])."\n";
