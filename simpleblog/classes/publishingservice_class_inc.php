<?php
/** Validated publishing operations; callers cannot bypass ownership with form fields. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class publishingservice extends ChisimbaObject
{
    private $store;
    private $policy;
    private $user;
    public function init()
    {
        $this->store=$this->getObject('publishingstore');
        $this->policy=$this->getObject('publishingpolicy');
        $this->user=$this->getObject('user','security');
    }
    public function classification()
    {
        $service=$this->getObject('classificationservice','classification');
        $service->registerProvider('simpleblog',$this->getObject('classificationprovider','simpleblog'));
        return $service;
    }
    public function read($id,$preview=false)
    {
        $post=$this->store->post($id);
        return $post && ($preview ? $this->policy->canEdit($post) : $this->policy->canRead($post)) ? $post : null;
    }
    public function save($id,$type,$scope,array $input)
    {
        $this->store->begin();
        try {
        $old=$id ? $this->store->lockPost($id) : null;
        if ($id && (!$old || !$this->policy->canEdit($old))) throw new DomainException('forbidden');
        if ($old && (!isset($input['version']) || !hash_equals(self::version($old),(string)$input['version']))) throw new DomainException('conflict');
        if ($old && ($type!==$old['post_type'] || $scope!==$old['blogid'])) throw new DomainException('scope_changed');
        if (!$this->policy->canCreate($type,$scope)) throw new DomainException('forbidden');
        foreach (array('title','content','tags','status') as $field)
            if (!isset($input[$field]) || !is_string($input[$field])) throw new DomainException('invalid');
        if (!in_array($input['status'],array('draft','posted'),true)) throw new DomainException('invalid');
        $title=trim($input['title']); $content=trim($input['content']); $tags=trim($input['tags']);
        if ($title==='' || mb_strlen($title)>250 || strlen($content)>500000 || mb_strlen($tags)>2000)
            throw new DomainException('invalid');
        $content=$this->getObject('richtextsanitizer','utilities')->cleanHtml($content);
        if (!isset($input['blocks']) && $input['status']==='posted' && trim(strip_tags($content))==='' && stripos($content,'<img')===false)
            throw new DomainException('empty_content');
        $composition=null;
        if (isset($input['blocks'])) {
            if (!is_array($input['blocks'])) throw new DomainException('invalid');
            $builder=$this->getObject('compositionservice','contentblocks');
            $blocks=$builder->validate($input['blocks']);
            $content=$builder->render($blocks);
            $composition=json_encode(['version'=>1,'revision'=>bin2hex(random_bytes(12)),'blocks'=>$blocks],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            if ($input['status']==='posted' && $content==='') throw new DomainException('empty_content');
        }
        $values=array('post_title'=>$title,'post_content'=>$content,'post_tags'=>$tags,'post_status'=>$input['status'],
            'modifierid'=>(string)$this->user->userId(),'datemodified'=>gmdate('Y-m-d H:i:s'));
        if(array_key_exists('featured_image',$input)) {
            if(!is_string($input['featured_image'])||!is_string($input['featured_alt']??''))throw new DomainException('invalid');
            $builder=$this->getObject('compositionservice','contentblocks');$image=$builder->emptyBlock('hero');
            $image['url']=$input['featured_image'];$image['alt']=$input['featured_alt']??'';
            $image=$builder->validate([$image])[0];
            $values['featured_image']=$image['url'];$values['featured_alt']=$image['alt'];
        }
        if ($composition!==null) {
            $values['composition_json']=$composition;
            if($old && empty($old['composition_json']) && empty($old['legacy_content_html']))$values['legacy_content_html']=$old['post_content'];
        }
        if ($input['status']==='posted' && empty($old['published_at'])) $values['published_at']=gmdate('Y-m-d H:i:s');
        if (!$old) $values+=array('post_type'=>$type,'blogid'=>$scope,'userid'=>(string)$this->user->userId(),'datecreated'=>gmdate('Y-m-d H:i:s'));
        $saved=$this->store->persist($id,$values);
        if (isset($input['blocks'])) {
            $classification=$this->classification();
            $names=array_values(array_filter(array_map('trim',explode(',',$tags)),static fn($tag)=>$tag!==''));
            $classification->tag('simpleblog',$saved,$names);
            $categories=$input['categories']??[];
            if (!is_array($categories)) throw new DomainException('invalid');
            $classification->assign('simpleblog',$saved,'category',$categories);
        }
        $this->store->commit(); return $saved;
        } catch (Throwable $e) { $this->store->rollback(); throw $e; }
    }
    public static function version(array $post)
    { return hash('sha256',json_encode(array_intersect_key($post,array_flip(array('id','userid','blogid','post_type','post_title','post_content','post_tags','post_status','datemodified','composition_json','published_at','featured_image','featured_alt'))))); }
    public function delete($id)
    {
        $this->store->begin();
        try {
            $post=$this->store->lockPost($id);
            if (!$post || !$this->policy->canEdit($post)) throw new DomainException('forbidden');
            $classification=$this->classification();
            $classification->assign('simpleblog',$id,'tag',[]);
            $classification->assign('simpleblog',$id,'category',[]);
            if (!$this->store->remove($id)) throw new RuntimeException('save_failed');
            $this->store->commit();
        } catch(Throwable $error) { $this->store->rollback();throw $error; }
    }
}
