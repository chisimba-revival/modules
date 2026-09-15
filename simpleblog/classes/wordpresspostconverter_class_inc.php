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
                case 'text-editor': $b=$this->block('text',$id,$index++,['text'=>$this->html($s['editor']??'')]);break;
                case 'heading':
                    $title=$this->plain($s['title']??$s['title_text']??'');
                    if(!$blocks&&$title===$this->plain($post['post_title']))continue 2;
                    $b=$this->block('text',$id,$index++,['title'=>$title]);break;
                case 'image':$b=$this->block('hero',$id,$index++,$this->photo($s['image']??[],$s));break;
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
                case 'post-comments':break; // WordPress interaction, not authored article content.
                default:throw new RuntimeException('Unreviewed widget '.$type.' in '.$id);
            }
            if($b!==null)$blocks[]=$b;
        }
        return $this->builder->validate($blocks);
    }
}
