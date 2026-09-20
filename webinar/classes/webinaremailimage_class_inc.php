<?php
/** Small registered banner derivatives for email; file delivery keeps its read policy. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinaremailimage extends ChisimbaObject
{
 public function init(){$this->loadClass('filedelivery','filemanager');}
 public function thumbnail(array $record){
  if(($record['status']??'')!=='published'||($record['kind']??'')!=='webinar')return null;
  $data=json_decode($record['payload'],true);$id=$data['image_file_id']??'';
  if(!$id){
   $url=parse_url($data['image']??'');$root=parse_url($this->getObject('altconfig','config')->getSiteRoot());
   if(!$url||isset($url['host'])&&($url['host']!==($root['host']??'')))return null;
   parse_str($url['query']??'',$query);
   if(($query['module']??'')!=='filemanager'||($query['action']??'')!=='file')return null;
   $id=$query['id']??'';
  }
  if(!is_string($id)||!preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id))return null;
  return $this->getObject('emailthumbnail','filemanager')->forFile($id);
 }
}
