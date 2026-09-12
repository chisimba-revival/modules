<?php
/** Reusable publishing navigation, independent of its placement. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class publishingsidebar extends ChisimbaObject
{
    public function init() {}
    public function render($kind,$type='site',$scope='site',$exclude='')
    {
        $policy=$this->getObject('publishingpolicy','simpleblog');$r=$this->getObject('publishingrenderer','simpleblog');
        $e=['publishingrenderer','escape'];$params=['scope'=>$type,'blogid'=>$scope];
        if($kind==='create')return $policy->canCreate($type,$scope)?$r->button('new','plus',$params+['action'=>'edit']):'';
        if(!$policy->canRead(['post_type'=>$type,'blogid'=>$scope,'post_status'=>'posted']))return '';
        if($kind==='share') {
            if($exclude!=='') {
                $post=$this->getObject('publishingstore','simpleblog')->post($exclude);
                if(!$post || !$policy->canRead($post))return '';
            }
            $url=html_entity_decode($this->uri($exclude!==''?['id'=>$exclude]:$params,'simpleblog'),ENT_QUOTES,'UTF-8');
            $this->appendArrayVar('headerParams','<script defer src="'.$e($this->getResourceUri('sharing.js','simpleblog')).'"></script>');
            return '<div class="chisimba-form-actions"><button type="button" class="button" data-blog-share="'.$e($url).'">'.$this->getObject('iconservice','ui')->render('share-2',['decorative'=>true]).'<span>'.$e($r->text('share')).'</span></button><button type="button" class="button" data-blog-copy="'.$e($url).'" data-copied="'.$e($r->text('copied')).'">'.$this->getObject('iconservice','ui')->render('copy',['decorative'=>true]).'<span>'.$e($r->text('copy_link')).'</span></button></div><p role="status" data-share-status></p><a href="'.$e($url).'">'.$e($r->text('permalink')).'</a>';
        }
        if($kind==='home')return $r->button('posts','newspaper',$params);
        if($kind==='archive')return $r->archives($type,$scope);
        if($kind==='feed')return $r->button('rss','rss',$params+['action'=>'getfeed']);
        if($kind==='latest'){
            $html='<ul>';$count=0;
            foreach($this->getObject('publishingstore','simpleblog')->listing($type,$scope) as $post){
                if($post['id']===$exclude || !$policy->canRead($post))continue;
                $html.='<li><a href="'.$e($this->uri(['id'=>$post['id']],'simpleblog')).'">'.$e($post['post_title']).'</a></li>';
                if(++$count===5)break;
            }
            return $count?$html.'</ul>':'<p>'.$e($r->text('empty')).'</p>';
        }
        if(in_array($kind,['tags','categories'],true)){
            $terms=$this->getObject('publishingstore','simpleblog')->visibleTerms($type,$scope,$kind==='tags'?'tag':'category');
            $html='<ul>';foreach($terms as $term){
                $html.='<li><a href="'.$e($this->uri($params+[$kind==='tags'?'tag':'category'=>$term['id']],'simpleblog')).'">'.$e($term['name']).'</a></li>';
            }return $html.'</ul>';
        }
        return '';
    }
    public function page($type,$scope,$exclude='')
    {
        $r=$this->getObject('publishingrenderer','simpleblog');$html='<aside class="chisimba-publishing-sidebar">';
        $blocks=['latest'=>'latestblogs','home'=>'bloghome','create'=>'createblog','categories'=>'blogcategories','tags'=>'blogtags','archive'=>'blogarchive','feed'=>'blogfeed','share'=>'blogshare'];
        foreach($blocks as $kind=>$name){
            $block=$this->newObject('block_'.$name,'simpleblog');$block->configure($type,$scope,$exclude);$body=$block->show();
            if($body==='')continue;
            $html.='<section class="chisimba-card"><h2>'.publishingrenderer::escape($block->title).'</h2>'.$body.'</section>';
        }
        return $html.'</aside>';
    }
}
