<?php
/** Attach reviewed source categories without replacing any webinar content. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com')throw new RuntimeException('Migration staging only');
$in=json_decode(file_get_contents('/tmp/ltb-event-taxonomy.json'),true,512,JSON_THROW_ON_ERROR);if($in['source']!=='https://learnthebirds.com'||$in['version']!==1)throw new RuntimeException('Unreviewed source');
$e->loadClass('classificationservice','classification');$terms=$e->getObject('classificationstore','classification');$records=$e->getObject('webinareditstore','webinar');$existing=[];
foreach(['category','tag'] as $kind){$v=classificationservice::vocabulary('site','site',$kind);$existing[$kind]=$terms->terms($v['id']);}
$ids=[];$plan=[];
foreach($in['terms'] as $key=>$source){
 if(!in_array($source['taxonomy'],['event_listing_category','event_listing_tag','post_tag'],true))throw new RuntimeException('Not a subject classification');
 $kind=$source['taxonomy']==='event_listing_category'?'category':'tag';$v=classificationservice::vocabulary('site','site',$kind);
 $name=trim(html_entity_decode($source['name'],ENT_QUOTES|ENT_HTML5,'UTF-8'));$slug=mb_strtolower(rawurldecode($source['slug']));
 if($name===''||mb_strlen($name)>150||preg_match('/[<>\x00-\x1f]/u',$name)||!preg_match('/^[\pL\pN_-]+$/uD',$slug))throw new RuntimeException('Invalid term');
 $found=null;foreach($existing[$kind] as $old)if(mb_strtolower($old['name'])===mb_strtolower($name)||$old['slug']===$slug){if(mb_strtolower($old['name'])!==mb_strtolower($name))throw new RuntimeException('Term collision');$found=$old;break;}
 $id=$found['id']??substr(hash('sha256','learnthebirds.com|event-term|'.$key),0,32);$ids[$key]=$id;
 $plan[$key]=['vocabulary'=>$v,'row'=>$found??['id'=>$id,'vocabulary_id'=>$v['id'],'name'=>$name,'slug'=>$slug,'parent_id'=>''],'source'=>$source,'found'=>(bool)$found];
}
foreach($plan as &$term){$source=$term['source'];$parent=$source['parent']?($ids[$source['taxonomy'].'|'.$source['parent']]??null):'';if($parent===null)throw new RuntimeException('Missing parent');if($term['found']&&$term['row']['parent_id']!==$parent)throw new RuntimeException('Parent conflict');$term['row']['parent_id']=$parent;}unset($term);
$links=[];$skipped=[];
foreach($in['events'] as $sourceId=>$keys){$id=substr(hash('sha256','learnthebirds.com|webinar|'.$sourceId),0,32);$record=$records->record($id);if(!$record){$skipped[]=$sourceId;continue;}if($record['kind']!=='webinar'||$record['source_key']!=='learnthebirds.com|webinar|'.$sourceId)throw new RuntimeException('Identity mismatch');$assigned=['category'=>[],'tag'=>[]];foreach($keys as $key){if(!isset($plan[$key]))throw new RuntimeException('Missing term');$assigned[$plan[$key]['vocabulary']['kind']][]=$ids[$key];}$links[$id]=$assigned;}
$apply=in_array('--apply',$argv,true);$changed=0;$unchanged=0;$records->begin();
try{
 foreach($plan as $term)if($apply)$terms->locked($term['vocabulary'],fn()=>$terms->saveTerm($term['row']));
 foreach($links as $id=>$assigned){$locked=$records->record($id,true);if(!$locked)throw new RuntimeException('Concurrent deletion');foreach($assigned as $kind=>$wanted){$v=classificationservice::vocabulary('site','site',$kind);$current=array_column($terms->itemTerms($v['id'],'webinar',$id),'id');sort($current);sort($wanted);if($current===$wanted){$unchanged++;continue;}if($current)throw new RuntimeException('Existing editorial classification differs; refusing overwrite');if($apply)$terms->locked($v,fn()=>$terms->replaceLinks($v['id'],'webinar',$id,$wanted));$changed++;}}
 if($apply)$records->commit();else $records->rollback();
}catch(Throwable $error){$records->rollback();throw $error;}
echo json_encode(['dry_run'=>!$apply,'webinars'=>count($links),'subject_terms'=>count($plan),'association_sets_added'=>$changed,'association_sets_unchanged'=>$unchanged,'unmatched_source_events'=>$skipped],JSON_THROW_ON_ERROR)."\n";
