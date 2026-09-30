<?php
/** Owned portrait-video collections for pages and articles; the skin owns presentation. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class videocollection extends ChisimbaObject
{
    public function init(){}
    private function text($key){return $this->getObject('compositionservice','contentblocks')->text($key);}
    private function e($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
    public function validate($items)
    {
        if(!is_array($items)||count($items)>100)throw new DomainException('invalid');
        $out=[];$ids=[];
        foreach($items as $item){
            if(!is_array($item))throw new DomainException('invalid');
            $row=[];foreach(['id'=>80,'title'=>500,'url'=>2048,'thumbnail'=>2048,'date'=>19,'topic'=>150] as $key=>$max){
                $value=$item[$key]??'';if(!is_string($value)||strlen($value)>$max)throw new DomainException('invalid');$row[$key]=trim($value);
            }
            if(!preg_match('/^[A-Za-z0-9_-]{1,80}$/D',$row['id'])||isset($ids[$row['id']]))throw new DomainException('invalid');
            $ids[$row['id']]=true;
            if($row['url']!==''){
                $embed=$this->getObject('contentmediaservice','contentblocks')->videoEmbed($row['url']);
                if(!$embed||!preg_match('~^https://(?:www\.tiktok\.com/player/v1/|www\.youtube-nocookie\.com/embed/|player\.vimeo\.com/video/)~',$embed))throw new DomainException('invalid');
            }
            if($row['thumbnail']!==''&&(!richtextsanitizer::safeUrl($row['thumbnail'])||str_starts_with($row['thumbnail'],'#')))throw new DomainException('invalid');
            if($row['date']!==''){
                $date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$row['date'],new DateTimeZone('UTC'));
                if(!$date||$date->format('Y-m-d H:i:s')!==$row['date'])throw new DomainException('invalid');
            }
            $out[]=$row;
        }
        return $out;
    }
    public function render(array $block)
    {
        $items=array_values(array_filter($this->validate($block['videos']??[]),fn($v)=>$v['url']!==''&&$v['title']!==''&&$v['date']!==''));
        if(!$items)return '';
        $direction=($block['gallery_order']??'asc')==='desc'?-1:1;
        usort($items,fn($a,$b)=>$direction*(strcmp($a['date'],$b['date'])?:strnatcmp($a['id'],$b['id'])));
        $dialogId='video-collection-'.$block['id'];$icons=$this->getObject('iconservice','ui');
        $html='<div data-video-collection data-order="'.($direction===1?'asc':'desc').'"><div class="chisimba-video-collection__toolbar" role="group" aria-label="'.$this->e($this->text('gallery_order')).'" hidden>';
        foreach(['desc'=>'newest','asc'=>'oldest'] as $order=>$label)$html.='<button type="button" class="button chisimba-button-secondary" data-collection-order="'.$order.'" aria-pressed="'.(($direction===1?'asc':'desc')===$order?'true':'false').'">'.$this->e($this->text($label)).'</button>';
        $html.='</div><div class="chisimba-video-collection__grid" data-collection-grid>';
        foreach($items as $i=>$item){
            $embed=$this->getObject('contentmediaservice','contentblocks')->videoEmbed($item['url']);
            $label=str_starts_with($embed,'https://www.tiktok.com/')?$this->text('open_tiktok'):$this->text('open_video');
            $html.='<article class="chisimba-publication-card" data-collection-card data-date="'.$this->e($item['date']).'" data-source-id="'.$this->e($item['id']).'"><a class="chisimba-video-collection__media" href="'.$this->e($item['url']).'" target="_blank" rel="noopener noreferrer" data-collection-play="'.$dialogId.'" data-embed="'.$this->e($embed).'" data-title="'.$this->e($item['title']).'" aria-haspopup="dialog" aria-label="'.$this->e($this->text('play').': '.$item['title']).'">';
            if($item['thumbnail']!=='')$html.='<img src="'.$this->e($item['thumbnail']).'" alt="" loading="lazy" decoding="async" width="270" height="480">';
            if($item['topic']!=='')$html.='<span class="chisimba-video-collection__topic">'.$this->e($item['topic']).'</span>';
            $html.='<span class="chisimba-video-collection__play" aria-hidden="true">'.$icons->render('play',['decorative'=>true]).'</span></a><div class="chisimba-publication-card__body"><h3>'.$this->e($item['title']).'</h3><a href="'.$this->e($item['url']).'" target="_blank" rel="noopener noreferrer">'.$this->e($label).'</a></div></article>';
        }
        $window=$this->newObject('window','ui');
        $window->setId($dialogId)->setTitle($block['title']?:$this->text('video_gallery'))->setContent('<div class="chisimba-video-collection__player" data-collection-player></div>');
        $html.='</div>'.$window->show().'</div>';
        $this->appendArrayVar('headerParams','<script defer src="'.$this->e($this->getResourceUri('videocollection.js','contentblocks')).'"></script>');
        return $html;
    }
}
