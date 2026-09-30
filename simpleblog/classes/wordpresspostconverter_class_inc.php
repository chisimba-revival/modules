<?php
/** Convert reviewed WordPress content into owned Chisimba blocks, not plugin code. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class wordpresspostconverter extends ChisimbaObject
{
    private $builder;
    private $replacements=[];
    public function init(){$this->builder=$this->getObject('compositionservice','contentblocks');}
    private function plain($s){return trim(html_entity_decode(strip_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8'));}
    private function html($s)
    {
        $s=strtr((string)$s,$this->replacements);
        $s=preg_replace('~\[caption\b[^\]]*\](.*?)\[/caption\]~is','<figure>$1</figure>',$s);
        if(preg_match('/\[\/?[A-Za-z_][A-Za-z0-9_-]*(?:\s+[^\]]*)?\]/',$s))throw new RuntimeException('Unreviewed shortcode in content');
        return $this->getObject('richtextsanitizer','utilities')->cleanHtml($s);
    }
    private function block($type,$postId,$index,array $fields=[])
    {
        $b=$this->builder->emptyBlock($type);$b['id']=substr(hash('sha256','wordpress|'.$postId.'|'.$index),0,24);return array_replace($b,$fields);
    }
    private function photo($image,$settings=[])
    {
        return ['url'=>strtr((string)($image['url']??''),$this->replacements),'alt'=>$this->plain($image['alt']??''),
            'caption'=>$this->plain(($settings['caption_source']??'attachment')==='custom'?($settings['caption']??$settings['custom_caption']??''):($image['caption']??''))];
    }
    public function convert(array $post,array $replacements)
    {
        $this->replacements=$replacements;$blocks=[];$id=$post['ID'];$index=0;
        if(!$post['widgets']){
            if(trim($post['post_content'])!=='')$blocks[]=$this->block('text',$id,$index++,['text'=>$this->html($post['post_content'])]);
        } else foreach($post['widgets'] as $widget){
            $s=$widget['settings'];$type=$widget['type'];$b=null;
            switch($type){
                case 'e-paragraph': $s['editor']='<p>'.($s['paragraph']??'').'</p>';
                case 'text-editor': $b=$this->block('text',$id,$index++,['text'=>$this->html($s['editor']??'')]);break;
                case 'e-heading':case 'heading':
                    $title=$this->plain($s['title']??$s['title_text']??'');
                    if(!$blocks&&$title===$this->plain($post['post_title']))continue 2;
                    $b=$this->block('text',$id,$index++,['title'=>$title]);break;
                case 'e-image':case 'image':$b=$this->block('hero',$id,$index++,$this->photo($s['image']??[],$s));break;
                case 'e-youtube': $s['youtube_url']=$s['source']??'';
                case 'video':case 'uael-video':
                    $url=($s['video_type']??'youtube')==='vimeo'?($s['vimeo_url']??$s['vimeo_link']??''):($s['youtube_url']??$s['youtube_link']??$s['hosted_url']['url']??'');
                    if(!$url)throw new RuntimeException('Missing video '.$id);
                    $b=$this->block('video',$id,$index++,['url'=>strtr($url,$replacements)]);break;
                case 'html':
                    $html=$s['html']??'';
                    if(preg_match('~<iframe\b[^>]*\bsrc=["\']([^"\']+)["\']~i',$html,$match)){
                        if(!$this->getObject('contentmediaservice','contentblocks')->videoEmbed($match[1]))throw new RuntimeException('Unrecognised embed '.$id);
                        $b=$this->block('video',$id,$index++,['url'=>$match[1]]);
                    }else $b=$this->block('text',$id,$index++,['text'=>$this->html($html)]);break;
                case 'blockquote':$b=$this->block('text',$id,$index++,['text'=>'<blockquote>'.$this->html($s['blockquote_content']??'').'</blockquote>'.(!empty($s['author_name'])?'<p>'.$this->html($s['author_name']).'</p>':'')]);break;
                case 'button':$url=strtr($s['link']['url']??'',$replacements);$b=$this->block('hero',$id,$index++,['button_url'=>$url,'button_label'=>$this->plain($s['text']??'')]);break;
                case 'image-gallery':case 'image-carousel':
                    $images=$s['wp_gallery']??$s['carousel']??$s['image_carousel']??[];
                    if(!$images)throw new RuntimeException('Missing gallery '.$id);
                    foreach(array_chunk($images,20) as $chunk)$blocks[]=$this->block('slider',$id,$index++,['slides'=>array_map(fn($image)=>$this->photo($image),$chunk)]);
                    break;
                case 'icon-list':
                    $items='';foreach(($s['icon_list']??[]) as $item){$label=$this->html($item['text']??'');$link=$item['link']['url']??'';if($link!=='')$label=$this->html('<a href="'.htmlspecialchars(strtr($link,$replacements),ENT_QUOTES,'UTF-8').'">'.$label.'</a>');$items.='<li>'.$label.'</li>';}
                    if($items==='')throw new RuntimeException('Empty list '.$id);
                    $b=$this->block('text',$id,$index++,['text'=>'<ul>'.$items.'</ul>']);break;
                case 'social-icons':
                    $items='';foreach(($s['social_icon_list']??[]) as $item){$link=$item['link']['url']??'';$label=parse_url($link,PHP_URL_HOST);if(!$label)throw new RuntimeException('Invalid social link '.$id);$items.='<li><a href="'.htmlspecialchars($link,ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</a></li>';}
                    $b=$this->block('text',$id,$index++,['text'=>$this->html('<ul class="chisimba-social-links">'.$items.'</ul>')]);break;
                case 'shortcode':
                    $shortcode=trim($s['shortcode']??'');
                    if(!isset($post['reviewed_shortcodes'][$shortcode]))throw new RuntimeException('Unreviewed shortcode in '.$id);
                    $reviewed=$post['reviewed_shortcodes'][$shortcode];
                    if(($reviewed['type']??null)==='video_gallery'){
                        $videos=$reviewed['videos'];
                        foreach($videos as &$video)$video['thumbnail']=strtr($video['thumbnail']??'',$replacements);unset($video);
                        $blocks[]=$this->block('video_gallery',$id,$index++,['videos'=>$videos,'gallery_order'=>$reviewed['order']??'asc']);
                    }else foreach($reviewed as $video)$blocks[]=$this->block('video',$id,$index++,['title'=>$video['title'],'url'=>$video['url']]);
                    break;
                case 'post-comments':break; // WordPress interaction, not authored article content.
                default:throw new RuntimeException('Unreviewed widget '.$type.' in '.$id);
            }
            if($b!==null)$blocks[]=$b;
        }
        return $this->builder->validate($blocks);
    }
}
