<?php
/** Speaker-owned links, serialised on the parent to enforce a four-link limit. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class speakerlinks extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_webinar_speaker_links',$pearDb,$errorCallback);$this->loadClass('authorbiographyvalue','userdetails');}
 public static function categories(){return ['website'=>'globe','facebook'=>'brand-facebook','instagram'=>'camera','youtube'=>'brand-youtube','tiktok'=>'brand-tiktok','linkedin'=>'briefcase-business','x'=>'at-sign','bluesky'=>'cloud','mastodon'=>'messages-square','flickr'=>'brand-flickr','podcast'=>'mic','publications'=>'book-open','other'=>'link'];}
 private function rows($id){return $this->selectPrepared('SELECT * FROM tbl_webinar_speaker_links WHERE speaker_id=? ORDER BY slot,id',[$id]);}
 public static function revision(array $rows){return hash('sha256',json_encode($rows));}
 public function listing($id){
  $r=$this->getObject('webinareditstore','webinar')->record($id);
  if(!$r||$r['kind']!=='speaker'||($r['status']!=='published'&&!$this->getObject('webinareditpolicy','webinar')->canManage()))return [];
  return $this->rows($id);
 }
 public function change($speaker,$operation,array $input){
  if(!$this->getObject('webinareditpolicy','webinar')->canManage())throw new DomainException('editor_forbidden');
  $this->beginTransaction();try{
   $r=$this->getObject('webinareditstore','webinar')->record($speaker,true);
   if(!$r||$r['kind']!=='speaker'||$r['status']==='trashed')throw new DomainException('editor_invalid');
   $rows=$this->rows($speaker);$id=$input['link_id']??'';$existing=null;
   foreach($rows as $row)if($row['id']===$id)$existing=$row;
   if(!hash_equals(self::revision($rows),(string)($input['links_version']??'')))throw new DomainException('editor_conflict');
   if($operation==='delete'){
    if(!$existing)throw new DomainException('editor_invalid');
    if($this->delete('id',$id)===false)throw new RuntimeException('editor_save_failed');
   }elseif($operation==='save'){
    $category=$input['category']??'';$url=trim($input['url']??'');$label=trim($input['label']??'');
    if(!isset(self::categories()[$category])||strlen($url)>2048||!authorbiographyvalue::safeUrl($url)||mb_strlen($label)>80||!preg_match('/^[a-f0-9]{32}$/D',$id))throw new DomainException('links_invalid');
    if(!$existing&&count($rows)>=4)throw new DomainException('links_limit');
    $slot=$existing['slot']??null;if($slot===null){$used=array_column($rows,'slot');for($slot=1;in_array($slot,$used);$slot++);}
    $value=['speaker_id'=>$speaker,'slot'=>$slot,'category'=>$category,'label'=>$label,'url'=>$url];
    if($existing){if($this->update('id',$id,$value)===false)throw new RuntimeException('editor_save_failed');}
    elseif($this->insert(['id'=>$id]+$value)===false)throw new RuntimeException('editor_save_failed');
   }else throw new DomainException('editor_invalid');
   $result=$this->rows($speaker);$this->commitTransaction();return $result;
  }catch(Throwable $e){$this->rollbackTransaction();throw $e;}
 }
}
