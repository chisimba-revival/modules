<?php
/** Read-only public event subject classifications; event types are not categories. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
define('SHORTINIT',true);require '/var/www/learnthebirds.com/wp-load.php';global $wpdb;
$out=['version'=>1,'source'=>'https://learnthebirds.com','events'=>[],'terms'=>[]];
foreach($wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE post_type='event_listing' AND post_status IN ('publish','expired') ORDER BY ID",ARRAY_A) as $event){
 $assigned=[];
 foreach($wpdb->get_results($wpdb->prepare("SELECT t.term_id,t.name,t.slug,tt.taxonomy,tt.parent FROM {$wpdb->term_relationships} r JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=r.term_taxonomy_id JOIN {$wpdb->terms} t ON t.term_id=tt.term_id WHERE r.object_id=%d AND tt.taxonomy IN ('event_listing_category','event_listing_tag','post_tag')",$event['ID']),ARRAY_A) as $term){$key=$term['taxonomy'].'|'.$term['term_id'];$out['terms'][$key]=$term;$assigned[]=$key;}
 $out['events'][(string)$event['ID']]=$assigned;
}
foreach($out['terms'] as $term){$parent=$term['parent'];$seen=[];while($parent){$key=$term['taxonomy'].'|'.$parent;if(isset($out['terms'][$key]))break;if(isset($seen[$key]))throw new RuntimeException('Category cycle');$seen[$key]=true;$row=$wpdb->get_row($wpdb->prepare("SELECT t.term_id,t.name,t.slug,tt.taxonomy,tt.parent FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id WHERE t.term_id=%d AND tt.taxonomy=%s",$parent,$term['taxonomy']),ARRAY_A);if(!$row)throw new RuntimeException('Missing category parent');$out['terms'][$key]=$row;$parent=$row['parent'];}}
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
