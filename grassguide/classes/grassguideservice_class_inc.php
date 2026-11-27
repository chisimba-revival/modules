<?php
/** Administrator-owned catalogue. Background enrichment with administrator maintenance. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguideservice extends ChisimbaObject
{
    public function init() { $this->getObject('grassguideinput','grassguide'); $this->getObject('grassguideproviders','grassguide'); }
    public function allowed(): bool { $u=$this->getObject('user','security'); return $u->isLoggedIn() && $u->isAdmin(); }
    public function guard(): void { if (!$this->allowed()) throw new DomainException('forbidden'); }
    private function store() { return $this->getObject('grassguidestore','grassguide'); }
    public function actor(): string { return (string)$this->getObject('user','security')->userId(); }
    public static function unpack(array $row): array { return array_merge(json_decode($row['payload'],true,32,JSON_THROW_ON_ERROR),array_diff_key($row,['payload'=>true])); }
    public function all(): array { $this->guard(); return array_map([self::class,'unpack'],$this->store()->all()); }
    public function one(string $id): ?array { $this->guard(); $r=$this->store()->one($id); return $r ? self::unpack($r) : null; }
    public function mediaFor(string $id): array { $this->guard(); return array_map([self::class,'unpack'],$this->store()->mediaFor($id)); }
    public function mediaOne(string $id): ?array { $this->guard(); $r=$this->store()->mediaOne($id); return $r ? self::unpack($r) : null; }
    public function history(string $id): array { $this->guard(); return $this->store()->history($id); }
    private function identity(string $id, $revision): int
    {
        if (!preg_match('/^[a-f0-9]{32}$/D',$id) || !is_scalar($revision) || !preg_match('/^(0|[1-9][0-9]{0,8})$/D',(string)$revision)) throw new DomainException('invalid');
        return (int)$revision;
    }
    public function save(string $id, array $input): array
    {
        $this->guard();
        $revision=$this->identity($id,$input['revision'] ?? '');
        $data=grassguideinput::species($input);
        $old=$this->one($id);
        $cover=grassguideinput::text($input+['representative_image'=>$old['representative_image']??''],'representative_image',32);
        if($cover!==''&&$cover!==($old['representative_image']??'')){
            if(!preg_match('/^[a-f0-9]{32}$/D',$cover))throw new DomainException('invalid_representative');
            $photo=$this->mediaOne($cover);
            if(!$photo||$photo['species_id']!==$id||$photo['kind']!=='image'||$photo['state']!=='active')throw new DomainException('invalid_representative');
        }
        $data['representative_image']=$cover;
        if ($old) {
            // Metadata is server-owned. Explicit human edits, including clearing a field, win.
            foreach (['_field_sources','_imported_values','_manual_fields','_block_nc_images'] as $key) $data[$key]=$old[$key] ?? [];
            foreach ($data as $key=>$value) if ($key[0]!=='_' && $value!==($old[$key] ?? null)) $data['_manual_fields'][$key]=true;
        }
        if ($old && ($old['genus'] !== $data['genus'] || $old['epithet'] !== $data['epithet'])) {
            $synonyms=array_filter(array_map('trim',explode("\n",$data['synonyms'])));
            $synonyms[]=$old['genus'].' '.$old['epithet'];
            $data['synonyms']=implode("\n",array_unique($synonyms));
            // A renamed taxon needs its external mapping reviewed again.
            $data['taxonomy_review']='pending'; $data['taxonomy_checked']='';
            if ($data['state']==='reviewed') $data['state']='draft';
        }
        return self::unpack($this->store()->writeSpecies($id,$revision,$data,$this->actor()));
    }
    /** Change catalogue membership without resubmitting or overwriting editorial fields. */
    public function setMembership(string $id, array $input): array
    {
        $this->guard();
        $revision=$this->identity($id,$input['revision'] ?? '');
        $operation=grassguideinput::choice($input,'operation',['remove','restore'],false);
        $row=$this->store()->one($id);
        if (!$row) throw new DomainException('missing');
        if ((int)$row['revision']!==$revision) throw new DomainException('conflict');
        $data=json_decode($row['payload'],true,32,JSON_THROW_ON_ERROR);
        if (($operation==='remove')===($data['state']==='archived')) throw new DomainException('conflict');
        // Restore as a draft: membership does not grant editorial review.
        $data['state']=$operation==='remove'?'archived':'draft';
        return self::unpack($this->store()->writeSpecies($id,$revision,$data,$this->actor()));
    }
    public function removeMediaBatch(string $species, array $input): array
    {
        $this->guard(); $this->identity($species,0);
        $selected=$input['selected']??null;
        if(!is_array($selected)||!count($selected)||count($selected)>200)throw new DomainException('invalid');
        $revisions=[];
        foreach($selected as $value){
            if(!is_string($value)||!preg_match('/^([a-f0-9]{32}):([1-9][0-9]{0,8})$/D',$value,$match)||isset($revisions[$match[1]]))throw new DomainException('invalid');
            $revisions[$match[1]]=(int)$match[2];
        }
        $this->store()->excludeMediaBatch($species,$revisions,$this->actor());
        return $this->one($species);
    }
    public function saveMedia(string $species, string $id, array $input): array
    {
        $this->guard(); $revision=$this->identity($id,$input['revision'] ?? '');
        $data=grassguideinput::media($input);$old=$this->mediaOne($id);
        if($old && grassguideproviders::photoId($old)===grassguideproviders::photoId($data) && grassguideproviders::photoId($data)!=='')foreach(['_provider','_external_id','_inat'] as $key)if(isset($old[$key]))$data[$key]=$old[$key];
        if($old&&!empty($old['_file_id'])&&$old['image_url']===$data['image_url'])$data['_file_id']=$old['_file_id'];
        return self::unpack($this->store()->writeMedia($species,$id,$revision,$data,$this->actor()));
    }
    public function seedPreview(): array
    {
        $this->guard(); $path=dirname(__DIR__).'/data/lowveld-kruger-v4.csv';
        $rows=grassguideinput::seed($path); $existing=$this->all(); $present=[]; $sources=[];
        foreach ($existing as $r) { $present[$r['scientific_name']]=true; $sources[$r['source_key'] ?? '']=true; }
        $missing=[]; foreach ($rows as $name=>$data) if (!isset($present[$name]) && !isset($sources['lowveld-v4:'.$name])) $missing[]=$name;
        return ['sha256'=>hash_file('sha256',$path),'total'=>count($rows),'missing'=>$missing,'rows'=>$rows];
    }
    public function import(string $hash): int
    {
        $this->guard(); $preview=$this->seedPreview();
        if (!hash_equals($preview['sha256'],$hash)) throw new DomainException('seed_changed');
        return $this->store()->importSeed($preview['rows'],$this->actor(),$hash);
    }
    public function export(): array
    {
        $this->guard(); $species=$this->all();
        foreach ($species as &$row) $row['media']=$this->mediaFor($row['id']);
        return ['format'=>'grassguide-editorial','version'=>1,'title'=>'Grasses of the Lowveld and Kruger','exported_at'=>gmdate('c'),'species'=>$species];
    }
    public function queueEnrichment(): int { $this->guard(); $this->getObject('grassguideproviders','grassguide'); return $this->store()->queueEnrichment(); }
    public function enrichmentJobs(?string $species=null): array { $this->guard(); return $this->store()->enrichmentJobs($species); }
    public function enrichmentErrors(): array { $this->guard(); return $this->store()->enrichmentErrors(); }
}
