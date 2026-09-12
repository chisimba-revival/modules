<?php
/** Ordered, owned content instances for posts and pages. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class compositionservice extends ChisimbaObject
{
    private $extensions=[];
    public function init() {}

    /** Trusted modules register new block providers; requests never name executable classes. */
    public function registerType($key,$icon,$provider)
    {
        if(!is_string($key)||!preg_match('/^[a-z][a-z0-9_]{1,29}$/D',$key)||isset($this->types()[$key]))throw new InvalidArgumentException('Invalid or duplicate block type');
        foreach(['label','validateBlock','renderBlock','editBlock'] as $method)if(!is_callable([$provider,$method]))throw new InvalidArgumentException('Incomplete block provider');
        $this->extensions[$key]=['icon'=>$icon,'provider'=>$provider];
    }
    public function provider($key) { return $this->extensions[$key]['provider']??null; }

    /** The existing content-block vocabulary, extended with compositional layouts. */
    public function types()
    {
        return ['text'=>'file-text','image_left'=>'panel-left','image_right'=>'panel-right',
            'hero'=>'image','reverse_hero'=>'image-up','video'=>'video','slider'=>'gallery-horizontal']+array_map(static fn($extension)=>$extension['icon'],$this->extensions);
    }

    public function text($key)
    {
        if(isset($this->extensions[$key]))return $this->extensions[$key]['provider']->label();
        return ucfirst($this->getObject('language','language')->code2Txt('mod_contentblocks_comp_'.$key,'contentblocks'));
    }

    public static function escape($value) { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }

    /** Empty editor placeholders are valid drafts; only populated blocks render. */
    public function emptyBlock($type)
    {
        if (!isset($this->types()[$type])) throw new DomainException('invalid');
        return ['id'=>bin2hex(random_bytes(12)),'type'=>$type,'title'=>'','text'=>'','url'=>'','alt'=>'','caption'=>'','button_label'=>'','button_url'=>'','button_position'=>'text','slides'=>[]];
    }

    /** Legacy HTML remains one editable text block until explicitly saved as a composition. */
    public function fromPost(array $post)
    {
        if (!empty($post['composition_json'])) {
            $value=json_decode($post['composition_json'],true);
            if (!is_array($value) || ($value['version']??null)!==1 || !is_array($value['blocks']??null)) throw new RuntimeException('Unsupported composition version');
            return $value['blocks'];
        }
        if (trim($post['post_content']??'')==='') return [];
        $block=$this->emptyBlock('text');$block['text']=$post['post_content'];return [$block];
    }

    private function field(array $input,$name,$limit)
    {
        $value=$input[$name]??'';
        if (!is_string($value) || strlen($value)>$limit) throw new DomainException('invalid');
        return trim($value);
    }

    /** Only managed relative URLs or explicit web URLs can become media sources. */
    private function url($url)
    {
        if ($url==='') return '';
        if (preg_match('/[\x00-\x20\x7f\\\\]/',$url) || str_starts_with($url,'//')) throw new DomainException('invalid');
        if (str_starts_with($url,'/') && !str_starts_with($url,'//')) return $url;
        if (preg_match('~^https?://~i',$url) && filter_var($url,FILTER_VALIDATE_URL)
            && !parse_url($url,PHP_URL_USER) && !parse_url($url,PHP_URL_PASS)) return $url;
        // Existing filemanager installations may return a path relative to the application.
        if (!str_contains($url,':') && !str_contains($url,'..') && preg_match('~^[a-zA-Z0-9_./%?=&+-]+$~D',$url)) return $url;
        throw new DomainException('invalid');
    }

    public function validate(array $blocks)
    {
        $encoded=json_encode($blocks);
        if ($encoded===false || count($blocks)>50 || strlen($encoded)>1000000) throw new DomainException('invalid');
        $out=[];$ids=[];
        foreach ($blocks as $input) {
            if (!is_array($input)) throw new DomainException('invalid');
            $type=$this->field($input,'type',30);$id=$this->field($input,'id',24);
            if (!isset($this->types()[$type]) || !preg_match('/^[a-f0-9]{24}$/D',$id) || isset($ids[$id])) throw new DomainException('invalid');
            $ids[$id]=true;
            $block=['id'=>$id,'type'=>$type,'title'=>$this->field($input,'title',500),
                'text'=>$this->getObject('richtextsanitizer','utilities')->cleanHtml($this->field($input,'text',250000)),
                'url'=>$this->url($this->field($input,'url',2048)), 'alt'=>$this->field($input,'alt',1000),
                'caption'=>$this->field($input,'caption',2000),
                'button_label'=>$this->field($input,'button_label',200),
                'button_url'=>$this->url($this->field($input,'button_url',2048)),'slides'=>[]];
            $block['button_position']=$this->field($input,'button_position',30)?:'text';
            if(!in_array($block['button_position'],['text','top-left','top-right','bottom-left','bottom-right'],true))throw new DomainException('invalid');
            if ($type==='slider') {
                $slides=$input['slides']??[];
                if (!is_array($slides) || count($slides)>20) throw new DomainException('invalid');
                foreach ($slides as $slide) {
                    if (!is_array($slide)) throw new DomainException('invalid');
                    $block['slides'][]=['url'=>$this->url($this->field($slide,'url',2048)),
                        'alt'=>$this->field($slide,'alt',1000),'caption'=>$this->field($slide,'caption',2000)];
                }
            }
            if($provider=$this->provider($type))$block['data']=$provider->validateBlock($input['data']??[]);
            $out[]=$block;
        }
        return $out;
    }

    /** Unsaved edits travel with every command; no block command writes content independently. */
    public function command(array $blocks,$command)
    {
        $parts=explode(':',(string)$command);
        if (in_array($parts[0]??'', ['add','addbefore'],true)) {
            if(count($blocks)>=50)throw new DomainException('invalid');
            $new=$this->emptyBlock($parts[1]??'');
            if($parts[0]==='add')$blocks[]=$new;
            else { $target=array_search($parts[2]??'',array_column($blocks,'id'),true);if($target===false)throw new DomainException('invalid');array_splice($blocks,$target,0,[$new]); }
            return $blocks;
        }
        $index=null;foreach($blocks as $i=>$block)if($block['id']===($parts[1]??''))$index=$i;
        if ($index===null) throw new DomainException('invalid');
        switch($parts[0]) {
            case 'focus': break;
            case 'end': $moving=$blocks[$index];array_splice($blocks,$index,1);$blocks[]=$moving;break;
            case 'before':
                $target=null;foreach($blocks as $n=>$candidate)if($candidate['id']===($parts[2]??''))$target=$n;
                if($target===null)throw new DomainException('invalid');
                $moving=$blocks[$index];array_splice($blocks,$index,1);if($index<$target)--$target;array_splice($blocks,$target,0,[$moving]);break;
            case 'remove': array_splice($blocks,$index,1);break;
            case 'duplicate': $copy=$blocks[$index];$copy['id']=bin2hex(random_bytes(12));array_splice($blocks,$index+1,0,[$copy]);break;
            case 'up': if($index>0)[$blocks[$index-1],$blocks[$index]]=[$blocks[$index],$blocks[$index-1]];break;
            case 'down': if($index<count($blocks)-1)[$blocks[$index+1],$blocks[$index]]=[$blocks[$index],$blocks[$index+1]];break;
            case 'slide': $blocks[$index]['slides'][]=['url'=>'','alt'=>'','caption'=>''];break;
            case 'remove_slide': $n=filter_var($parts[2]??'',FILTER_VALIDATE_INT);if($n===false)throw new DomainException('invalid');array_splice($blocks[$index]['slides'],$n,1);break;
            default: throw new DomainException('invalid');
        }
        if(count($blocks)>50)throw new DomainException('invalid');return $blocks;
    }

    private function figure(array $block)
    {
        if($block['url']==='')return '';
        return '<figure><img src="'.self::escape($block['url']).'" alt="'.self::escape($block['alt']).'" loading="lazy">'
            .($block['caption']!==''?'<figcaption>'.self::escape($block['caption']).'</figcaption>':'').'</figure>';
    }

    /** Shared semantic rendering; the active skin supplies layout and appearance. */
    public function render(array $blocks)
    {
        $html='';
        foreach($this->validate($blocks) as $block) {
            $copy=($block['title']!==''?'<h2>'.self::escape($block['title']).'</h2>':'').$block['text'];
            $image=$this->figure($block);$body='';
            if(in_array($block['type'],['hero','reverse_hero'],true) && $block['button_label']!=='' && $block['button_url']!=='') {
                $button='<a class="button chisimba-button-primary" href="'.self::escape($block['button_url']).'">'.self::escape($block['button_label']).'</a>';
                if($block['button_position']==='text'||$block['url']==='')$copy.='<div class="chisimba-form-actions">'.$button.'</div>';
                else {
                    $image='<figure><div class="chisimba-hero-image"><img src="'.self::escape($block['url']).'" alt="'.self::escape($block['alt']).'" loading="lazy"><div class="chisimba-hero-image__action chisimba-hero-image__action--'.$block['button_position'].'">'.$button.'</div></div>'
                        .($block['caption']!==''?'<figcaption>'.self::escape($block['caption']).'</figcaption>':'').'</figure>';
                }
            }
            if($provider=$this->provider($block['type'])) { $html.=$provider->renderBlock($block['data']);continue; }
            switch($block['type']) {
                case 'text': $body=$copy;break;
                case 'hero': case 'image_left': $body=$image.'<div>'.$copy.'</div>';break;
                case 'reverse_hero': case 'image_right': $body='<div>'.$copy.'</div>'.$image;break;
                case 'video':
                    if($block['url']==='')break;
                    $embed=$this->getObject('contentmediaservice','contentblocks')->videoEmbed($block['url']);
                    $body=$copy.($embed ? '<iframe src="'.self::escape($embed).'" title="'.self::escape($block['title']?:$this->text('video')).'" loading="lazy" allowfullscreen></iframe>'
                        :'<video src="'.self::escape($block['url']).'" controls preload="metadata" playsinline></video>');
                    if($block['caption']!=='')$body.='<p>'.self::escape($block['caption']).'</p>';break;
                case 'slider':
                    $slides='';foreach($block['slides'] as $slide)$slides.=$this->figure($slide);
                    if($slides!=='')$body=$copy.'<div class="chisimba-slider-controls" hidden><button type="button" class="button" data-slide-direction="-1">'.self::escape($this->text('previous')).'</button><button type="button" class="button" data-slide-direction="1">'.self::escape($this->text('next')).'</button></div><div class="chisimba-content-slider" tabindex="0" role="region" aria-label="'.self::escape($block['title']?:$this->text('slider')).'">'.$slides.'</div>';break;
            }
            if(trim(strip_tags($body))==='' && !preg_match('/<(img|video|iframe)\b/',$body))continue;
            $html.='<section class="chisimba-content-block chisimba-content-block--'.str_replace('_','-',$block['type']).'">'.$body.'</section>';
        }
        if(str_contains($html,'chisimba-content-slider'))$this->appendArrayVar('headerParams','<script defer src="'.self::escape($this->getResourceUri('compositionview.js','contentblocks')).'"></script>');
        return $html;
    }
}
