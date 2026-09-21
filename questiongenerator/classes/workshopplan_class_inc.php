<?php
/** Lossless UTF-8 section planning and bounded question allocation. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class workshopplan extends ChisimbaObject
{
 public function build($source,$count,array $capacity){
  $limit=(int)$capacity['sourceBytes'];if($limit<1000)throw new DomainException('capacity_unknown');
  $parts=[];$remaining=$source;
  while(strlen($remaining)>$limit){
   $part=mb_strcut($remaining,0,$limit,'UTF-8');
   $boundary=strrpos($part,"\n\n");
   if($boundary!==false&&$boundary>strlen($part)/2)$part=substr($part,0,$boundary+2);
   $parts[]=$part;$remaining=substr($remaining,strlen($part));
  }
  if($remaining!==''){
   if($parts&&mb_strlen(trim($remaining),'UTF-8')<100){
    $previous=array_pop($parts);$head=mb_strcut($previous,0,strlen($previous)-400,'UTF-8');
    $remaining=substr($previous,strlen($head)).$remaining;$parts[]=$head;
   }
   $parts[]=$remaining;
  }
  if(count($parts)>30)throw new DomainException('capacity_sections');
  $total=max($count,count($parts));$allocation=array_fill(0,count($parts),1);
  for($i=count($parts);$i<$total;$i++)$allocation[($i-count($parts))%count($parts)]++;
  return ['parts'=>$parts,'counts'=>$allocation,'next'=>0,'questions'=>[],'issues'=>[],'requested'=>$count,'capacity'=>$capacity];
 }
}
