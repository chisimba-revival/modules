<?php
/** Public site posts for reusable newsletter summaries. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class publishingnewsfeed extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_simpleblog_posts',$pearDb,$errorCallback);}
 public function latest($limit=2){
  $rows=$this->selectPrepared("SELECT id,post_title,featured_image,featured_alt FROM tbl_simpleblog_posts WHERE post_type='site' AND blogid='site' AND post_status='posted' AND (required_tier_code IS NULL OR required_tier_code='') AND COALESCE(published_at,datecreated)<=? ORDER BY COALESCE(published_at,datecreated) DESC,id DESC LIMIT ".max(1,min(10,(int)$limit)),[gmdate('Y-m-d H:i:s')]);
  $root=rtrim($this->getObject('altconfig','config')->getSiteRoot(),'/');$items=[];
  foreach($rows as $row){
   $url=$root.'/index.php?module=simpleblog&action=view&id='.rawurlencode($row['id']);
   $image=html_entity_decode($row['featured_image']??'',ENT_QUOTES|ENT_HTML5,'UTF-8');$parts=parse_url($image);$thumb=null;
   if($parts&&(!isset($parts['host'])||strcasecmp($parts['host'],parse_url($root,PHP_URL_HOST))===0)){
    parse_str($parts['query']??'',$q);
    if(($q['module']??'')==='filemanager'&&($q['action']??'')==='file'&&is_string($q['id']??null))$thumb=$this->getObject('emailthumbnail','filemanager')->forFile($q['id']);
    // Imported WordPress uploads are already public; never expose arbitrary local file routes.
    elseif(str_starts_with($parts['path']??'','/wp-content/uploads/')&&!str_contains($parts['path'],'..'))$thumb=['url'=>$root.$parts['path'],'width'=>250];
   }elseif($parts&&in_array(strtolower($parts['scheme']??''),['https','http'],true)&&empty($parts['user'])&&empty($parts['pass']))$thumb=['url'=>$image,'width'=>250];
   $items[]=['title'=>html_entity_decode($row['post_title'],ENT_QUOTES|ENT_HTML5,'UTF-8'),'url'=>$url,'image'=>$thumb];
  }return $items;
 }
}
