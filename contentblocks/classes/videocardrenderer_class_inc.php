<?php
/** Reusable, progressively enhanced video cards; skin owns their appearance. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class videocardrenderer extends ChisimbaObject
{
    public function init(){}
    public static function videoId($id){return is_string($id)&&preg_match('/^[A-Za-z0-9_-]{11}$/D',$id);}
    private function escape($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
    public function cards(array $videos,$dialogId,$playLabel)
    {
        $html='';$icons=$this->getObject('iconservice','ui');
        foreach($videos as $v){
            if(!self::videoId($v['video_id']??null))throw new InvalidArgumentException('Invalid video identity');
            $id=$v['video_id'];$title=html_entity_decode($v['title'],ENT_QUOTES|ENT_HTML5,'UTF-8');
            $href='https://www.youtube.com/watch?v='.$id;
            $html.='<article class="chisimba-publication-card" data-video-card="'.$id.'"><a class="chisimba-publication-card__media chisimba-video-card__play" href="'.$href.'" target="_blank" rel="noopener noreferrer" data-video-id="'.$id.'" data-video-title="'.$this->escape($title).'" data-ui-open="'.$this->escape($dialogId).'" aria-haspopup="dialog" aria-label="'.$this->escape($playLabel.': '.$title).'">'
                .'<img class="chisimba-publication-card__image" src="https://i.ytimg.com/vi/'.$id.'/hqdefault.jpg" alt="" width="480" height="360" loading="lazy" decoding="async">'
                .'<span class="chisimba-video-card__overlay" aria-hidden="true">'.$icons->render('play',['decorative'=>true]).'</span></a>'
                .'<div class="chisimba-publication-card__body"><h2 class="chisimba-publication-card__title"><a href="'.$href.'" target="_blank" rel="noopener noreferrer" data-video-id="'.$id.'" data-video-title="'.$this->escape($title).'" data-ui-open="'.$this->escape($dialogId).'" aria-haspopup="dialog">'.$this->escape($title).'</a></h2>'
                .'<p class="chisimba-publication-meta">'.$this->escape($v['duration']).'</p></div></article>';
        }
        return $html;
    }
    public function player($id,$title)
    {
        $window=$this->newObject('window','ui');
        $window->setId($id)->setTitle($title)->setWidth('960px')->setContent('<div class="chisimba-video-player" data-video-player></div>');
        $this->appendArrayVar('headerParams','<script defer src="'.$this->escape($this->getResourceUri('videogallery.js','contentblocks')).'"></script>');
        return $window->show();
    }
}
