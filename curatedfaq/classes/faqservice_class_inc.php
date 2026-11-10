<?php
/** Site FAQ policy and transactional editing; no request-supplied owner or scope. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class faqservice extends ChisimbaObject
{
    private $store;
    public function init(){$this->store=$this->getObject('faqstore','curatedfaq');}
    public function canManage(){return $this->getObject('user','security')->isLoggedIn()&&$this->getObject('user','security')->isAdmin();}
    private function requireManager(){if(!$this->canManage())throw new DomainException('forbidden');}
    public function classification()
    {$c=$this->getObject('classificationservice','classification');$c->registerProvider('curatedfaq',$this);return $c;}
    public function classificationCanCreate($type,$scope){return $type==='site'&&$scope==='site'&&$this->canManage();}
    public function classificationAccess($id)
    {$row=$this->store->item($id);return $row?['scope_type'=>'site','scope_id'=>'site','read'=>$row['status']==='published'||$this->canManage(),'edit'=>$this->canManage()]:null;}
    public function read($id,$preview=false)
    {$row=$this->store->item($id);return $row&&($preview?$this->canManage():$row['status']==='published')?$row:null;}
    public function listing($manage=false,$search='',$category='',$page=1)
    {if($manage)$this->requireManager();return $this->store->listing($manage,mb_substr($search,0,200),$category,$page);}
    public function categories($manage=false)
    {if($manage){$this->requireManager();return $this->classification()->managementTerms('site','site','category');}return $this->store->visibleCategories();}
    public function itemCategories($id){return $this->classification()->forItem('curatedfaq',$id,'category');}
    public static function validate(array $input,callable $clean)
    {
        foreach(['id','question','answer','status','revision','display_order','new_category'] as $key)if(!is_string($input[$key]??null))throw new DomainException('invalid');
        if(!preg_match('/^[a-f0-9]{32}$/D',$input['id'])||!preg_match('/^(?:new|[1-9][0-9]{0,9})$/D',$input['revision']))throw new DomainException('invalid');
        $input['question']=trim($input['question']);
        if($input['question']===''||mb_strlen($input['question'])>250||strlen($input['answer'])>250000||!in_array($input['status'],['draft','published','archived'],true))throw new DomainException('invalid');
        $order=filter_var($input['display_order'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>99999]]);
        if($order===false||mb_strlen($input['new_category'])>150||!is_array($input['categories']??null)||count($input['categories'])>20||count(array_filter($input['categories'],'is_string'))!==count($input['categories']))throw new DomainException('invalid');
        $input['answer']=$clean($input['answer']);
        if($input['status']==='published'&&trim(strip_tags($input['answer']))===''&&!preg_match('/<img\b/i',$input['answer']))throw new DomainException('empty_answer');
        $input['display_order']=$order;return $input;
    }
    public function save(array $input)
    {
        $this->requireManager();
        $input=self::validate($input,fn($html)=>$this->getObject('richtextsanitizer','utilities')->cleanHtml($html));
        return $this->store->transaction(function()use($input){
            $this->requireManager();$old=$this->store->item($input['id'],true);
            if(($old&&$input['revision']!==(string)$old['revision'])||(!$old&&$input['revision']!=='new'))throw new DomainException('conflict');
            $categories=$input['categories'];$classification=$this->classification();
            if(trim($input['new_category'])!==''){
                $term=$classification->saveTerm('site','site','category',$input['new_category']);$categories[]=$term['id'];
            }
            $row=['id'=>$input['id'],'question'=>$input['question'],'answer'=>$input['answer'],'status'=>$input['status'],'display_order'=>$input['display_order'],
                'revision'=>$old?(int)$old['revision']+1:1,'modifierid'=>(string)$this->getObject('user','security')->userId(),'datemodified'=>gmdate('Y-m-d H:i:s')];
            if(!$old)$row+=['creatorid'=>$row['modifierid'],'datecreated'=>$row['datemodified']];
            $saved=$this->store->persist($row,(bool)$old);
            $classification->assign('curatedfaq',$saved['id'],'category',array_values(array_unique($categories)));
            return $saved;
        });
    }
}
