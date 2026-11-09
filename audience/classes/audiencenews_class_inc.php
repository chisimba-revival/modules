<?php
/** Frozen public news cards, shared by manual and scheduled newsletters. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class audiencenews extends ChisimbaObject
{
 public function init(){}
 public function snapshot(){return $this->getObject('modules','modulecatalogue')->checkIfRegistered('simpleblog')?$this->getObject('publishingnewsfeed','simpleblog')->latest(2):[];}
 public function text(array $items){if(!$items)return '';return $this->getObject('audiencerenderer','audience')->text('news_heading')."\n\n".implode("\n\n",array_map(fn($i)=>$i['title']."\n".$i['url'],$items));}
 public function html(array $items){
  if(!$items)return '';$e=static fn($v)=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$html='<h2>'.$e($this->getObject('audiencerenderer','audience')->text('news_heading')).'</h2>';
  foreach($items as $item){$html.='<section>';if($item['image'])$html.='<p><a href="'.$e($item['url']).'"><img src="'.$e($item['image']['url']).'" width="250" alt="'.$e($item['title']).'" style="max-width:100%;height:auto"></a></p>';$html.='<h3><a href="'.$e($item['url']).'">'.$e($item['title']).'</a></h3></section>';}
  return $html;
 }
}
