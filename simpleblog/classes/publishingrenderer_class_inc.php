<?php
/** Shared semantic output for pages and existing site/course/personal blocks. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class publishingrenderer extends ChisimbaObject
{
    public function init() {}
    public function text($key)
    { return ucfirst($this->getObject('language','language')->code2Txt('mod_simpleblog26_'.$key,'simpleblog')); }
    public static function escape($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
    public function button($label,$icon,$params,$primary=false)
    {
        return '<a class="button '.($primary?'chisimba-button-primary':'chisimba-button-secondary').'" href="'.self::escape($this->uri($params,'simpleblog')).'">'
            .$this->getObject('iconservice','ui')->render($icon,array('decorative'=>true)).'<span>'.self::escape($this->text($label)).'</span></a>';
    }
    public function identity($type,$scope)
    {
        if ($type==='site') return $this->text('site');
        if ($type==='personal') return $this->text('personal').' — '.$this->getObject('user','security')->fullname($scope);
        $course=$this->getObject('dbcontext','context')->getContextDetails($scope);
        return $this->text('context').' — '.($course['title']??$scope).' ('.$scope.')';
    }
    public function posts($type,$scope,$manage=false,$page=1,$status='all',$search='',$tag='',$year=0,$month=0,$single=false,$category='')
    {
        $policy=$this->getObject('publishingpolicy');
        if (!$policy->validScope($type,$scope) || ($manage && !$policy->canCreate($type,$scope))) return '';
        $rows=$this->getObject('publishingstore')->listing($type,$scope,$manage?$status:'posted',$page,$search,$tag,$year,$month,$manage&&!$policy->managesScope($type,$scope)?(string)$this->getObject('user','security')->userId():null,$category);
        $more=!$single && count($rows)>10; $rows=array_slice($rows,0,$single?1:10); $html='';
        foreach ($rows as $post) {
            if (!($manage?$policy->canEdit($post):$policy->canRead($post))) continue;
            $link=$this->uri(array('action'=>$manage?'preview':'view','id'=>$post['id']),'simpleblog');
            $html.='<article class="chisimba-publication-card" role="listitem">';
            if(!empty($post['featured_image']))$html.='<div class="chisimba-publication-card__media"><img class="chisimba-publication-card__image" src="'.self::escape($post['featured_image']).'" alt="'.self::escape($post['featured_alt']??'').'" loading="lazy"></div>';
            else $html.='<div class="chisimba-publication-card__media" aria-hidden="true"></div>';
            $html.='<div class="chisimba-publication-card__body"><h2 class="chisimba-publication-card__title"><a href="'.self::escape($link).'">'.self::escape($post['post_title']).'</a></h2>';
            $html.=$this->metadata($post);
            $html.='<p>'.self::escape(mb_substr(html_entity_decode(strip_tags(preg_replace('~</(?:p|h[1-6]|li|section|div)>~i','$0 ',$post['post_content'])),ENT_QUOTES,'UTF-8'),0,240)).'</p>';
            if ($manage) $html.='<p>'.self::escape($this->text($post['post_status']==='posted'?'published':'draft')).'</p>'.$this->button('edit','pencil',array('action'=>'edit','id'=>$post['id']));
            if(!$manage)$html.='<div class="chisimba-publication-card__actions chisimba-form-actions">'.$this->button('read_post','arrow-right',array('action'=>'view','id'=>$post['id'])).'</div>';
            $html.='</div></article>';
        }
        if ($html==='') $html='<p>'.self::escape($this->text('empty')).'</p>';
        else $html='<div class="chisimba-publication-card-grid" role="list">'.$html.'</div>';
        $params=array('action'=>$manage?'manage':'view','scope'=>$type,'blogid'=>$scope,'status'=>$status,'search'=>$search,'tag'=>$tag,'year'=>$year,'month'=>$month,'category'=>$category);
        $html.='<nav class="chisimba-form-actions" aria-label="'.self::escape($this->text('pages')).'">';
        if ($page>1) $html.=$this->button('previous','arrow-left',array_merge($params,array('page'=>$page-1)));
        if ($more) $html.=$this->button('next','arrow-right',array_merge($params,array('page'=>$page+1)));
        return $html.'</nav>';
    }
    public function tags($type,$scope)
    {
        return $this->getObject('publishingsidebar','simpleblog')->render('tags',$type,$scope);
    }
    public function archives($type,$scope)
    {
        if (!$this->getObject('publishingpolicy')->canRead(array('post_type'=>$type,'blogid'=>$scope,'post_status'=>'posted'))) return '';
        $html='<ul>';
        foreach($this->getObject('publishingstore')->archive($type,$scope) as $row) {
            $label=sprintf('%04d-%02d',(int)$row['year'],(int)$row['month']);
            $html.='<li><a href="'.self::escape($this->uri(array('scope'=>$type,'blogid'=>$scope,'year'=>$row['year'],'month'=>$row['month']),'simpleblog')).'">'.$label.' ('.(int)$row['total'].')</a></li>';
        }
        return $html.'</ul>';
    }
    /** Shared author/date line for cards and full posts. Stored publishing times are UTC. */
    public function metadata(array $post)
    {
        $icons=$this->getObject('iconservice','ui');
        $author=self::escape($this->getObject('user','security')->fullname($post['userid']));
        $raw=$post['published_at']??$post['datecreated']??'';
        $date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',(string)$raw,new DateTimeZone('UTC'));
        $html='<p class="chisimba-publication-meta"><span>'.$icons->render('user',['decorative'=>true]).$author.'</span>';
        if($date){
            $days=(int)$date->setTime(0,0)->diff(new DateTimeImmutable('today',new DateTimeZone('UTC')))->format('%r%a');
            $label=$days===0?$this->text('today'):($days===1?$this->text('yesterday'):($days>1?str_replace('[-days-]',(string)$days,$this->text('days_ago')):$date->format('j M Y')));
            $full=$date->format('j F Y, H:i').' UTC';
            $html.='<span>'.$icons->render('calendar',['decorative'=>true]).'<time datetime="'.self::escape($date->format('c')).'" title="'.self::escape($full).'" aria-label="'.self::escape($full).'" tabindex="0" data-relative-date data-today="'.self::escape($this->text('today')).'" data-yesterday="'.self::escape($this->text('yesterday')).'" data-days-ago="'.self::escape($this->text('days_ago')).'">'.self::escape($label).'</time></span>';
            $this->appendArrayVar('headerParams','<script defer src="'.self::escape($this->getResourceUri('relative-date.js','utilities')).'"></script>');
        }
        return $html.'</p>';
    }
    public function article(array $post)
    {
        $e=array(__CLASS__,'escape');
        $html='<article class="chisimba-form-card chisimba-form-card--wide"><header><h1>'.$e($post['post_title']).'</h1>'.$this->metadata($post).'</header>';
        $body=!empty($post['composition_json'])
            ? $this->getObject('compositionservice','contentblocks')->render($this->getObject('compositionservice','contentblocks')->fromPost($post))
            : $this->getObject('richtextsanitizer','utilities')->cleanHtml($post['post_content']);
        $html.='<div class="reading-surface">'.$body.'</div>';
        if (trim($post['post_tags']??'')!=='') $html.='<p>'.$e($this->text('tags')).': '.$e($post['post_tags']).'</p>';
        $bio=$this->getObject('authorbiographyservice','userdetails')->forUser($post['userid']);
        if (trim($bio['biography'])!=='') $html.=$this->getObject('authorbiographyrenderer','userdetails')->person(array_merge($bio,array('userid'=>$post['userid'],'name'=>$this->getObject('user','security')->fullname($post['userid']))));
        return $html.'</article>';
    }
    private function absolute($url)
    {
        if (preg_match('~^https?://~',$url)) return $url;
        $root=rtrim($this->getObject('altconfig','config')->getItem('KEWL_SITE_ROOT'),'/');
        return str_starts_with($url,'/') ? preg_replace('~^(https?://[^/]+).*$~','$1',$root).$url : $root.'/'.ltrim($url,'/');
    }
    public function feed($type,$scope)
    {
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_XML1|ENT_QUOTES,'UTF-8');
        $xml='<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>'.$e($this->identity($type,$scope)).'</title><link>'.$e($this->absolute($this->uri(array('scope'=>$type,'blogid'=>$scope),'simpleblog'))).'</link><description>'.$e($this->text('posts')).'</description>';
        foreach ($this->getObject('publishingstore')->listing($type,$scope) as $post) {
            if (!$this->getObject('publishingpolicy')->canRead($post)) continue;
            $url=$this->absolute($this->uri(array('id'=>$post['id']),'simpleblog'));
            $published=strtotime(($post['published_at']??$post['datecreated']).' UTC');
            $xml.='<item><pubDate>'.gmdate(DATE_RSS,$published).'</pubDate><title>'.$e($post['post_title']).'</title><link>'.$e($url).'</link><guid isPermaLink="false">'.$e($post['id']).'</guid><description>'.$e(strip_tags($post['post_content'])).'</description></item>';
        }
        return $xml.'</channel></rss>';
    }
}
