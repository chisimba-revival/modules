<?php
/** Rights triage and the single app-export boundary; editorial records remain private. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguiderights extends ChisimbaObject
{
    private bool $operatorRead=false;
    private function readGuard(): void { if(!$this->operatorRead)$this->service()->guard(); }
    public function operatorExport(?string $previewMode=null): array
    {
        if(PHP_SAPI!=='cli')throw new DomainException('forbidden');
        $this->operatorRead=true;try { return $this->appExport($previewMode); } finally { $this->operatorRead=false; }
    }
    /** Only the filtered app projection is public; scope is always restored. */
    public function publicExport(): array
    {
        $this->operatorRead=true;
        try {
            if(empty($this->policy()['app_updates']))throw new DomainException('unpublished');
            return $this->appExport();
        } finally { $this->operatorRead=false; }
    }
    /** Read-only HTTP contract; no caller can select a different publication mode. */
    public function publicResponse(string $method, string $etag=''): array
    {
        $headers=['Content-Type'=>'application/json; charset=UTF-8','Cache-Control'=>'no-store','Access-Control-Allow-Origin'=>'*','Access-Control-Expose-Headers'=>'ETag','X-Content-Type-Options'=>'nosniff'];
        if($method==='OPTIONS')return ['status'=>204,'headers'=>$headers+['Access-Control-Allow-Methods'=>'GET, HEAD, OPTIONS','Access-Control-Allow-Headers'=>'If-None-Match'],'body'=>''];
        if(!in_array($method,['GET','HEAD'],true))return ['status'=>405,'headers'=>$headers+['Allow'=>'GET, HEAD, OPTIONS'],'body'=>'{"error":"method"}'];
        try { $bundle=$this->publicExport(); }
        catch(DomainException $e) { if($e->getMessage()!=='unpublished')throw $e;return ['status'=>404,'headers'=>$headers,'body'=>'{"error":"unavailable"}']; }
        $content=$bundle;unset($content['generated_at']);
        $revision=hash('sha256',json_encode($content,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        $headers['ETag']='"'.$revision.'"';$bundle['revision']=$revision;
        if($etag===$headers['ETag'])return ['status'=>304,'headers'=>$headers,'body'=>''];
        return ['status'=>200,'headers'=>$headers,'body'=>$method==='HEAD'?'':json_encode($bundle,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
    }
    public const FIELDS=['authority','common_name','other_names','synonyms','description','diagnostic','similar','habitat','ecological_status','succession','life_cycle','grazing_value','heads','awns','powo_id'];
    public function init() { $this->getObject('grassguideservice','grassguide'); }
    private function service() { return $this->getObject('grassguideservice','grassguide'); }
    private function store() { return $this->getObject('grassguidestore','grassguide'); }
    public static function licence(string $raw): string
    {
        $code=strtoupper(trim($raw));
        if(preg_match('~^https?://creativecommons.org/licenses/(by(?:-nc)?(?:-nd|-sa)?)/(3\.0|4\.0)/?(?:deed(?:\.[a-z-]+)?)?$~i',$raw,$m)) $code='CC-'.strtoupper($m[1]).'-'.$m[2];
        if(preg_match('~^https?://creativecommons.org/publicdomain/zero/1\.0/?$~i',$raw))$code='CC0-1.0';
        if(preg_match('/^CC-BY-NC(?:-SA)?-(3\.0|4\.0)$/D',$code))return 'nc';
        if($code==='CC0-1.0'||preg_match('/^CC-BY(?:-SA)?-(3\.0|4\.0)$/D',$code))return 'commercial_ok';
        return 'rights_unknown';
    }
    public function policy(): array
    {
        $this->readGuard(); $row=$this->store()->rightsOne('publication');
        $data=$row?grassguideservice::unpack($row):['mode'=>'noncommercial','revision'=>0];
        if(!in_array($data['mode'],['noncommercial','commercial'],true))throw new DomainException('invalid');
        return $data;
    }
    public function savePolicy(array $input): array
    {
        $this->service()->guard();
        $mode=grassguideinput::choice($input,'mode',['noncommercial','commercial'],false);
        return grassguideservice::unpack($this->store()->writeRights('publication',$this->revision($input),['mode'=>$mode,'app_updates'=>($input['app_updates']??'')==='1'],$this->service()->actor(),str_repeat('0',32)));
    }
    private function revision(array $input): int
    {
        $value=$input['revision']??'';if(!is_scalar($value)||!preg_match('/^(0|[1-9][0-9]{0,8})$/D',(string)$value))throw new DomainException('invalid');return (int)$value;
    }
    private static function fingerprint(array $content): string
    {
        // Refresh timestamps do not change the work covered by an explicit permission.
        unset($content['retrieved_at']);
        if(isset($content['source']))unset($content['source']['retrieved_at']);
        return hash('sha256',json_encode($content,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
    }
    /** Current targets are derived from content; no write or API call is needed to audit. */
    public function inventory(): array
    {
        $this->readGuard();$tracking=[];
        foreach($this->store()->rightsAll() as $r)$tracking[$r['target']]=grassguideservice::unpack($r);
        $items=[];$species=array_map([grassguideservice::class,'unpack'],$this->store()->all());$media=[];
        foreach($species as $s) {
            if($s['state']==='archived')continue;
            $media[$s['id']]=array_values(array_filter(array_map([grassguideservice::class,'unpack'],$this->store()->mediaFor($s['id'])),fn($m)=>$m['state']==='active'));
            foreach($media[$s['id']] as $m) {
                $content=array_intersect_key($m,array_flip(['kind','image_url','source_url','youtube_id','creator','licence','caption','alt_text','role']));
                $items[]=$this->item('media:'.$m['id'],$s,'media',$m['role'],$m['licence'],$m['source_url'],$m['creator'],$content,$tracking);
            }
            foreach(self::FIELDS as $field) {
                if(empty($s[$field]))continue;
                $source=$s['_field_sources'][$field]??[];
                $items[]=$this->item('field:'.$s['id'].':'.$field,$s,'field',$field,$source['licence']??'',$source['url']??'',$source['citation']??'', ['value'=>$s[$field],'source'=>$source],$tracking);
            }
        }
        $commercial=[];foreach($items as $item)if($item['kind']==='media'&&$item['content']['kind']==='image'&&self::eligible($item,'commercial'))$commercial[$item['species_id']]=($commercial[$item['species_id']]??0)+1;
        foreach($items as &$item)$item['priority']=$item['kind']==='media'&&$item['classification']==='nc'?(empty($commercial[$item['species_id']])?'priority_1':'priority_2'):'priority_review';unset($item);
        return ['items'=>$items,'species'=>$species,'media'=>$media,'commercial_images'=>$commercial];
    }
    private function item(string $target,array $s,string $kind,string $field,string $licence,string $url,string $creator,array $content,array $tracking): array
    {
        $fingerprint=self::fingerprint($content);$task=$tracking[$target]??['revision'=>0,'task_status'=>'todo','assignee'=>'','due_date'=>'','replacement_url'=>'','permission_evidence'=>'','public_credit'=>'','decision'=>'inherit','task_notes'=>''];
        return ['target'=>$target,'species_id'=>$s['id'],'name'=>$s['scientific_name'],'search'=>implode(' ',[$s['scientific_name'],$s['common_name'],$s['other_names'],$s['synonyms'],$creator]),'kind'=>$kind,'field'=>$field,'licence'=>$licence,'source_url'=>$url,'creator'=>$creator,'classification'=>self::licence($licence),'fingerprint'=>$fingerprint,'content'=>$content,'task'=>$task,'stale'=>!empty($task['fingerprint'])&&!hash_equals($task['fingerprint'],$fingerprint)];
    }
    public static function eligible(array $item,string $mode): bool
    {
        if(!in_array($mode,['noncommercial','commercial'],true))throw new DomainException('invalid');
        $decision=$item['task']['decision'];
        if($decision==='exclude')return false;
        if($decision==='commercial_permission'&&!$item['stale']&&!empty($item['task']['permission_evidence'])&&!empty($item['task']['public_credit']))return true;
        if($item['classification']==='commercial_ok')return true;
        if($mode==='commercial')return false;
        // Preserve existing sourced/seed text for the free development edition; unknown media never gains permission this way.
        return $item['classification']==='nc'||$item['kind']==='field';
    }
    public function target(string $target): array
    {
        foreach($this->inventory()['items'] as $item)if($item['target']===$target)return $item;
        throw new DomainException('rights_missing');
    }
    public function saveTask(string $target,array $input): array
    {
        $this->service()->guard();$item=$this->target($target);
        if(!hash_equals($item['fingerprint'],grassguideinput::text($input,'fingerprint',64,true)))throw new DomainException('conflict');
        $data=['task_status'=>grassguideinput::choice($input,'task_status',['todo','planned','resolved'],false),'decision'=>grassguideinput::choice($input,'decision',['inherit','exclude','commercial_permission'],false),'fingerprint'=>$item['fingerprint']];
        foreach(['assignee'=>200,'permission_evidence'=>4000,'public_credit'=>1500,'task_notes'=>4000] as $key=>$limit)$data[$key]=grassguideinput::text($input,$key,$limit);
        $data['replacement_url']=grassguideinput::url($input,'replacement_url');$data['due_date']=grassguideinput::text($input,'due_date',10);
        if($data['due_date']!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$data['due_date'])||!checkdate((int)substr($data['due_date'],5,2),(int)substr($data['due_date'],8,2),(int)substr($data['due_date'],0,4))))throw new DomainException('invalid_date');
        if($data['decision']==='commercial_permission'&&($data['permission_evidence']===''||$data['public_credit']===''))throw new DomainException('permission_required');
        $result=grassguideservice::unpack($this->store()->writeRights($target,$this->revision($input),$data,$this->service()->actor(),$item['species_id']));
        return $result;
    }
    /** Administrator-only app bundle; permission evidence/tasks never leave this boundary. */
    public function appExport(?string $previewMode=null): array
    {
        $this->readGuard();$mode=$previewMode??$this->policy()['mode'];
        if(!in_array($mode,['noncommercial','commercial'],true))throw new DomainException('invalid');
        $inventory=$this->inventory();$map=[];foreach($inventory['items'] as $i)$map[$i['target']]=$i;
        $result=[];$omitted=['media'=>0,'fields'=>0];
        foreach($inventory['species'] as $s) {
            if($s['state']==='archived')continue;
            $out=array_intersect_key($s,array_flip(['id','scientific_name','genus','epithet']));$out['sources']=[];$out['media']=[];
            foreach(self::FIELDS as $field) {
                $out[$field]=$field==='heads'?[]:'';$item=$map['field:'.$s['id'].':'.$field]??null;if(!$item)continue;
                if(!self::eligible($item,$mode)){$omitted['fields']++;continue;}
                $out[$field]=$s[$field];$source=$s['_field_sources'][$field]??null;
                if($item['task']['decision']==='commercial_permission'&&!$item['stale'])$source=['provider'=>'permission','url'=>$item['source_url'],'licence'=>'permission','citation'=>$item['task']['public_credit']];
                if($source)$out['sources'][$field]=array_intersect_key($source,array_flip(['provider','url','licence','citation','retrieved_at']));
            }
            $out['ecology_review']=$s['ecology_review'];
            foreach($inventory['media'][$s['id']] as $m){$item=$map['media:'.$m['id']];if(!self::eligible($item,$mode)){$omitted['media']++;continue;}
                $data=$item['content'];if($item['task']['decision']==='commercial_permission'&&!$item['stale']){$data['original_licence']=$data['licence'];$data['licence']='permission';$data['permission_credit']=$item['task']['public_credit'];}$out['media'][]=$data;
            }
            $result[]=$out;
        }
        return ['format'=>'lowveld-field-guide','version'=>1,'generated_at'=>gmdate('c'),'publication_mode'=>$mode,'omitted'=>$omitted,'species'=>$result];
    }
}
