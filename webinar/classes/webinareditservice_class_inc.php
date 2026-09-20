<?php
/** Native webinar/speaker editing; publication and classification commit together. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinareditservice extends ChisimbaObject
{
    private $store;
    public function init(){$this->store=$this->getObject('webinareditstore','webinar');}
    public function allowed(){return $this->getObject('webinareditpolicy','webinar')->canManage();}
    private function requireEditor(){if(!$this->allowed())throw new DomainException('editor_forbidden');}
    public function read($id){$this->requireEditor();return $this->store->record($id);}
    public function classification()
    {
        $service=$this->getObject('classificationservice','classification');
        $service->registerProvider('webinar',$this);return $service;
    }
    public function classificationCanCreate($type,$scope){return $type==='site'&&$scope==='site'&&$this->allowed();}
    public function classificationAccess($id)
    {
        $row=$this->store->record($id);if(!$row||$row['kind']!=='webinar')return null;
        return ['scope_type'=>'site','scope_id'=>'site','read'=>$row['status']==='published','edit'=>$this->allowed()];
    }
    public static function version(array $row)
    { $values=[];foreach(['id','kind','title','status','presented_at','payload','source_hash'] as $key)$values[$key]=(string)($row[$key]??'');return hash('sha256',json_encode($values,JSON_THROW_ON_ERROR)); }
    /** Exact local dates reject PHP's rollover of impossible dates and DST gaps. */
    public static function localDate($value,$timezone)
    {
        if(!is_string($value)||!is_string($timezone)||!in_array($timezone,DateTimeZone::listIdentifiers(),true))throw new DomainException('editor_date');
        $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,new DateTimeZone($timezone));
        if(!$date||$date->format('Y-m-d\TH:i')!==$value)throw new DomainException('editor_date');
        return $date;
    }
    public static function webUrl($value)
    {
        if($value==='')return '';
        if(!is_string($value)||strlen($value)>2000||!filter_var($value,FILTER_VALIDATE_URL))throw new DomainException('editor_url');
        $parts=parse_url($value);if(!in_array(strtolower($parts['scheme']??''),['http','https'],true)||isset($parts['user'])||isset($parts['pass']))throw new DomainException('editor_url');
        return $value;
    }
    /** Validate one record without dropping legacy payload fields or source identity. */
    public function values($kind,array $input,?array $old=null)
    {
        if(!in_array($kind,['webinar','speaker'],true))throw new DomainException('editor_invalid');
        foreach(['title','description','image','image_alt','status'] as $key)if(!is_string($input[$key]??null))throw new DomainException('editor_invalid');
        $title=trim($input['title']);if($title===''||mb_strlen($title)>500||strlen($input['description'])>500000||!in_array($input['status'],['draft','published'],true))throw new DomainException('editor_invalid');
        $description=$this->getObject('richtextsanitizer','utilities')->cleanHtml($input['description']);
        if($input['status']==='published'&&trim(strip_tags($description))==='')throw new DomainException('editor_description');
        $builder=$this->getObject('compositionservice','contentblocks');$image=$builder->emptyBlock('hero');$image['url']=$input['image'];$image['alt']=$input['image_alt'];
        $image=$builder->validate([$image])[0];
        $payload=$old?json_decode($old['payload'],true,512,JSON_THROW_ON_ERROR):[];
        if(($payload['image']??'')!==$image['url'])unset($payload['image_file_id'],$payload['banner']);
        $payload['description']=$description;$payload['image']=$image['url'];$payload['image_alt']=$image['alt'];
        $date='';
        if($kind==='webinar'){
            // Duration is the editor contract; retain end-time input for older clients.
            if(array_key_exists('duration_minutes',$input)){
                $minutes=filter_var($input['duration_minutes'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>10080]]);
                if($minutes===false)throw new DomainException('editor_duration');
                $input['ends_at']=$input['starts_at']===''?'':self::localDate($input['starts_at'],$input['timezone'])->add(new DateInterval('PT'.$minutes.'M'))->format('Y-m-d\TH:i');
            }
            foreach(['starts_at','ends_at','timezone','recording','joining_url','tags'] as $key)if(!is_string($input[$key]??null))throw new DomainException('editor_invalid');
            if(mb_strlen($input['tags'])>2000||!is_array($input['speakers']??null)||count($input['speakers'])>30)throw new DomainException('editor_invalid');
            if($input['status']==='draft'&&$input['starts_at']===''&&$input['ends_at']===''){
                if(!in_array($input['timezone'],DateTimeZone::listIdentifiers(),true))throw new DomainException('editor_date');
                $date='';$payload['ends_at']='';
            }else{
                $start=self::localDate($input['starts_at'],$input['timezone']);$end=self::localDate($input['ends_at'],$input['timezone']);
                if($end<=$start)throw new DomainException('editor_end');
                $date=$start->format('Y-m-d H:i:s');$payload['ends_at']=$end->format('Y-m-d H:i:s');
            }
            $payload['timezone']=$input['timezone'];
            $payload['recording']=self::webUrl($input['recording']);
            if($payload['recording']&&!$this->getObject('contentmediaservice','contentblocks')->videoEmbed($payload['recording']))throw new DomainException('editor_recording');
            $payload['joining_url']=self::webUrl($input['joining_url']);
            $payload['registration_open']=($input['registration_open']??'')==='1';$payload['cancelled']=($input['cancelled']??'')==='1';
            $speakers=[];foreach($input['speakers'] as $id){$speaker=$this->store->record($id);if(!$speaker||$speaker['kind']!=='speaker'||$speaker['status']!=='published')throw new DomainException('editor_speaker');$speakers[]=$id;}
            $payload['speakers']=array_values(array_unique($speakers));
            if($input['status']==='published'&&!$speakers)throw new DomainException('editor_speaker');
        }
        $id=$old['id']??($input['create_id']??bin2hex(random_bytes(16)));
        if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))throw new DomainException('editor_invalid');
        if($old&&!isset($payload['original_source_hash']))$payload['original_source_hash']=$old['source_hash'];
        // Never infer ownership from the last editor of an imported/legacy record.
        if(!$old)$payload['created_by']=(string)$this->getObject('user','security')->userId();
        $payload['editorial_revision']=bin2hex(random_bytes(16));$payload['edited_by']=(string)$this->getObject('user','security')->userId();$payload['edited_at']=gmdate('Y-m-d H:i:s');
        $encoded=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        return ['id'=>$id,'kind'=>$kind,'source_key'=>$old['source_key']??'native|'.$kind.'|'.$id,'source_hash'=>hash('sha256',$title.'|'.$date.'|'.$encoded),'title'=>$title,'status'=>$input['status'],'presented_at'=>$date,'payload'=>$encoded];
    }
    /** Reversible removal: preserve bookings and source identity, restore privately. */
    public function setTrashed($id,$version,$restore=false)
    {
        $this->requireEditor();$this->store->begin();
        try{
            $row=$this->store->record($id,true);
            if(!$row||$row['kind']!=='webinar')throw new DomainException('editor_forbidden');
            if(!is_string($version)||!hash_equals(self::version($row),$version))throw new DomainException('editor_conflict');
            if($restore ? $row['status']!=='trashed' : !in_array($row['status'],['draft','published'],true))throw new DomainException('editor_invalid');
            $row['status']=$restore?'draft':'trashed';
            $this->store->persist($row,true);$saved=$this->store->record($id);
            if(!$saved||$saved['status']!==$row['status'])throw new RuntimeException('webinar_storage_failed');
            $this->store->commit();return $saved;
        }catch(Throwable $error){$this->store->rollback();throw $error;}
    }
    public function save($id,$kind,array $input)
    {
        $this->requireEditor();$this->store->begin();
        try{
            $old=$id!==''?$this->store->record($id,true):null;
            if($id===''&&isset($input['create_id'])&&$this->store->record($input['create_id'],true))throw new DomainException('editor_conflict');
            if($id!==''&&(!$old||$old['kind']!==$kind))throw new DomainException('editor_forbidden');
            if($old&&$old['status']==='trashed')throw new DomainException('editor_forbidden');
            if($old&&(!is_string($input['version']??null)||!hash_equals(self::version($old),$input['version'])))throw new DomainException('editor_conflict');
            $row=$this->values($kind,$input,$old);$this->store->persist($row,(bool)$old);
            if($kind==='webinar'){
                $categories=$input['categories']??[];if(!is_array($categories))throw new DomainException('editor_invalid');
                $c=$this->classification();$c->assign('webinar',$row['id'],'category',$categories);
                $c->tag('webinar',$row['id'],array_values(array_filter(array_map('trim',explode(',',$input['tags'])),fn($x)=>$x!=='')));
            }
            $saved=$this->store->record($row['id']);if(!$saved||!hash_equals(self::version($row),self::version($saved)))throw new RuntimeException('webinar_storage_failed');
            $this->store->commit();return $saved;
        }catch(Throwable $e){$this->store->rollback();throw $e;}
    }
}
