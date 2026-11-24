<?php
/** All persistence uses the shared checked dbTable boundary. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguidestore extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') { parent::init('tbl_grassguide_species', $pearDb, $errorCallback); }
    public function rightsAll(): array { return $this->selectExactPrepared('SELECT * FROM tbl_grassguide_rights ORDER BY target'); }
    public function rightsOne(string $target): ?array { return $this->selectExactPrepared('SELECT * FROM tbl_grassguide_rights WHERE target=?',[$target])[0] ?? null; }
    public function writeRights(string $target,int $revision,array $data,string $actor,string $species): array
    {
        return $this->transactionWithSavepoint(function() use($target,$revision,$data,$actor,$species) {
            $old=$this->rightsOne($target);
            if (($old && (int)$old['revision']!==$revision) || (!$old && $revision!==0)) throw new DomainException('conflict');
            $json=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE); $now=gmdate('Y-m-d H:i:s');
            if($old) {
                if($this->executeExactPrepared('UPDATE tbl_grassguide_rights SET payload=?,revision=revision+1,updated_at=?,updated_by=? WHERE target=? AND revision=?',[$json,$now,$actor,$target,$revision])!==1)throw new DomainException('conflict');
            } else $this->executeExactPrepared('INSERT INTO tbl_grassguide_rights (id,target,payload,revision,updated_at,updated_by) VALUES (?,?,?,1,?,?)',[md5($target),$target,$json,$now,$actor]);
            $this->audit($species,$actor,$target==='publication'?'publication_saved':'rights_saved',['target'=>$target,'rights'=>$data]);
            return $this->rightsOne($target);
        });
    }
    public function all(): array { return $this->selectExactPrepared('SELECT * FROM tbl_grassguide_species ORDER BY scientific_name'); }
    public function one(string $id): ?array { return $this->selectExactPrepared('SELECT * FROM tbl_grassguide_species WHERE id=?', [$id])[0] ?? null; }
    public function mediaFor(string $id): array { return $this->selectExactPrepared('SELECT * FROM tbl_grassguide_media WHERE species_id=? ORDER BY created_at,id', [$id]); }
    public function mediaOne(string $id): ?array { return $this->selectExactPrepared('SELECT * FROM tbl_grassguide_media WHERE id=?', [$id])[0] ?? null; }
    public function history(string $id): array { return $this->selectExactPrepared('SELECT actor,action,created_at FROM tbl_grassguide_audit WHERE species_id=? ORDER BY created_at DESC,id DESC LIMIT 20', [$id]); }
    public function audit(string $species, string $actor, string $action, array $snapshot): void
    {
        $this->executeExactPrepared('INSERT INTO tbl_grassguide_audit (id,species_id,actor,action,snapshot,created_at) VALUES (?,?,?,?,?,?)', [bin2hex(random_bytes(16)), $species, $actor, $action, json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), gmdate('Y-m-d H:i:s')]);
    }
    public function writeSpecies(string $id, int $revision, array $data, string $actor, string $sourceKey = ''): array
    {
        return $this->transactionWithSavepoint(function () use ($id, $revision, $data, $actor, $sourceKey) {
            $current = $this->one($id);
            if (($current && (int)$current['revision'] !== $revision) || (!$current && $revision !== 0)) throw new DomainException('conflict');
            $name = $data['genus'].' '.$data['epithet'];
            if ($this->selectExactPrepared('SELECT id FROM tbl_grassguide_species WHERE scientific_name=? AND id<>?', [$name, $id])) throw new DomainException('duplicate');
            $payload = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $now = gmdate('Y-m-d H:i:s');
            if ($current) {
                $affected = $this->executeExactPrepared('UPDATE tbl_grassguide_species SET scientific_name=?,payload=?,revision=revision+1,updated_at=?,updated_by=? WHERE id=? AND revision=?', [$name,$payload,$now,$actor,$id,$revision]);
                if ($affected !== 1) throw new DomainException('conflict');
            } else {
                $this->executeExactPrepared('INSERT INTO tbl_grassguide_species (id,scientific_name,source_key,payload,revision,updated_at,updated_by) VALUES (?,?,?,?,1,?,?)', [$id,$name,$sourceKey ?: null,$payload,$now,$actor]);
            }
            $this->audit($id,$actor,$current ? 'species_saved' : 'species_created', $data);
            return $this->one($id);
        });
    }
    public function importSeed(array $rows, string $actor, string $hash): int
    {
        return $this->transaction(function () use ($rows,$actor,$hash) {
            $count = 0;
            foreach ($rows as $name=>$data) {
                $key = 'lowveld-v4:'.$name;
                if ($this->selectExactPrepared('SELECT id FROM tbl_grassguide_species WHERE source_key=? OR scientific_name=?', [$key,$name])) continue;
                $id = substr(hash('sha256', $key), 0, 32);
                $this->executeExactPrepared('INSERT INTO tbl_grassguide_species (id,scientific_name,source_key,payload,revision,updated_at,updated_by) VALUES (?,?,?,?,1,?,?)', [$id,$name,$key,json_encode($data,JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),gmdate('Y-m-d H:i:s'),$actor]);
                $this->audit($id,$actor,'seed_imported',['sha256'=>$hash,'original'=>$data]);
                $count++;
            }
            return $count;
        });
    }
    public function writeMedia(string $species, string $id, int $revision, array $data, string $actor): array
    {
        return $this->transactionWithSavepoint(function () use ($species,$id,$revision,$data,$actor) {
            if (!$this->one($species)) throw new DomainException('missing');
            $current = $this->mediaOne($id);
            if (($current && ($current['species_id'] !== $species || (int)$current['revision'] !== $revision)) || (!$current && $revision !== 0)) throw new DomainException('conflict');
            $payload = json_encode($data,JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $now = gmdate('Y-m-d H:i:s');
            if ($current) {
                if ($this->executeExactPrepared('UPDATE tbl_grassguide_media SET payload=?,revision=revision+1,updated_at=? WHERE id=? AND revision=? AND species_id=?', [$payload,$now,$id,$revision,$species]) !== 1) throw new DomainException('conflict');
            } else {
                $this->executeExactPrepared('INSERT INTO tbl_grassguide_media (id,species_id,payload,revision,created_at,updated_at) VALUES (?,?,?,1,?,?)', [$id,$species,$payload,$now,$now]);
            }
            $this->audit($species,$actor,'media_saved',['id'=>$id,'media'=>$data]);
            return $this->mediaOne($id);
        });
    }
    /** Species row lock serialises replacement with in-flight provider imports. */
    public function writePhotoBatch(string $species,int $revision,array $media,string $mode,string $actor): array
    {
        $this->getObject('grassguiderights','grassguide');
        return $this->transactionWithSavepoint(function() use($species,$revision,$media,$mode,$actor) {
            $row=$this->selectExactPrepared('SELECT * FROM tbl_grassguide_species WHERE id=? FOR UPDATE',[$species])[0]??null;
            if(!$row)throw new DomainException('missing');
            if((int)$row['revision']!==$revision)throw new DomainException('conflict');
            $data=json_decode($row['payload'],true,32,JSON_THROW_ON_ERROR);
            if($data['state']==='archived')throw new DomainException('missing');
            foreach($media as $id=>$image)$this->writeMedia($species,$id,0,$image,$actor);
            if($mode==='replace_nc') {
                foreach($this->mediaFor($species) as $old){
                    $m=json_decode($old['payload'],true,32,JSON_THROW_ON_ERROR);
                    if($m['kind']!=='image'||$m['state']!=='active'||grassguiderights::licence($m['licence'])!=='nc')continue;
                    $m['state']='archived';$this->writeMedia($species,$old['id'],(int)$old['revision'],$m,$actor);
                }
                $data['_block_nc_images']=true;
            } elseif($mode==='resume')$data['_block_nc_images']=false;
            return $this->writeSpecies($species,$revision,$data,$actor);
        });
    }
    public function queueEnrichment(): int
    {
        return $this->transaction(function () {
            $count=0;$now=gmdate('Y-m-d H:i:s');$stale=gmdate('Y-m-d H:i:s',time()-30*86400);
            foreach($this->all() as $row) {
                $data=json_decode($row['payload'],true);if(($data['state'] ?? '')==='archived')continue;
                foreach(array_keys(grassguideproviders::HOSTS) as $provider) {
                    $id=md5($row['id'].':'.$provider);
                    // No reset of active/retry jobs: their provider cooldown must survive clicks.
                    $old=$this->selectExactPrepared('SELECT * FROM tbl_grassguide_jobs WHERE id=?',[$id])[0] ?? null;
                    if(!$old) {
                        $count+=$this->executeExactPrepared('INSERT IGNORE INTO tbl_grassguide_jobs (id,species_id,provider,status,due_at,updated_at,attempts,result_json,last_error) VALUES (?,?,?,\'queued\',?,?,0,\'{}\',\'\')',[$id,$row['id'],$provider,$now,$now]);
                    } elseif(in_array($old['status'],['done','unmatched'],true) && ($old['updated_at']<$stale || ($provider==='grassbase' && !array_key_exists('key_features',json_decode($old['result_json'],true)['fields']??[]) && $old['status']==='done'))) {
                        $count+=$this->executeExactPrepared('UPDATE tbl_grassguide_jobs SET status=\'queued\',due_at=?,attempts=0,last_error=\'\' WHERE id=? AND status=? AND updated_at=?',[$now,$id,$old['status'],$old['updated_at']]);
                    }
                }
            }
            return $count;
        });
    }
    /** Only the exact current-name GBIF match identifier may enter the public bundle. */
    public function matchedGbifKey(string $species,string $name): string
    {
        $row=$this->selectExactPrepared('SELECT result_json FROM tbl_grassguide_jobs WHERE species_id=? AND provider=? AND status=?',[$species,'gbif','done'])[0]??null;
        $r=$row?json_decode($row['result_json'],true):null;
        $key=(string)($r['identifier']??'');
        return !empty($r['matched'])&&($r['name']??'')===$name&&preg_match('/^[1-9][0-9]{0,12}$/D',$key)?$key:'';
    }
    public function enrichmentJobs(?string $species=null): array
    {
        return $species===null?$this->selectExactPrepared('SELECT provider,status,COUNT(*) AS total FROM tbl_grassguide_jobs GROUP BY provider,status ORDER BY provider,status'):$this->selectExactPrepared('SELECT provider,status,last_error,updated_at,due_at FROM tbl_grassguide_jobs WHERE species_id=? ORDER BY provider',[$species]);
    }
    public function enrichmentErrors(): array
    { return $this->selectExactPrepared('SELECT provider,last_error,COUNT(*) AS total,MIN(due_at) AS next_attempt FROM tbl_grassguide_jobs WHERE last_error<>\'\' GROUP BY provider,last_error ORDER BY provider'); }
    /** Caller owns the per-provider database lock. Every completed result is cached. */
    public function nextEnrichment(string $provider): ?array
    {
        $row=$this->selectExactPrepared('SELECT * FROM tbl_grassguide_jobs WHERE provider=? AND status IN (\'queued\',\'retry\',\'running\') AND due_at<=? ORDER BY (status=\'running\') DESC,due_at,id LIMIT 1',[$provider,gmdate('Y-m-d H:i:s')])[0] ?? null;
        if(!$row)return null;
        $this->executeExactPrepared('UPDATE tbl_grassguide_jobs SET status=\'running\',attempts=attempts+1,updated_at=? WHERE id=?',[gmdate('Y-m-d H:i:s'),$row['id']]);
        $row['attempts']=(int)$row['attempts']+1;return $row;
    }
    public function failEnrichment(array $job,int $status,int $retryAfter,string $reason=''): void
    {
        $blocked=in_array($status,[401,403,429],true);
        $delay=max($retryAfter,$status===403 || $status===401?86400:($status===429?60:min(21600,60*(2**min(8,$job['attempts'])))));
        // Never shorten Retry-After. Other jobs for this provider share its cooldown.
        $due=gmdate('Y-m-d H:i:s',time()+$delay);$state=$job['attempts']>=4?'failed':'retry';
        $error=$reason ?: ('HTTP '.$status.($blocked?' — provider paused':' — request failed'));
        $this->transaction(function() use($job,$blocked,$due,$state,$error) {
            $this->executeExactPrepared('UPDATE tbl_grassguide_jobs SET status=?,due_at=?,last_error=?,updated_at=? WHERE id=?',[$state,$due,$error,gmdate('Y-m-d H:i:s'),$job['id']]);
            if($blocked)$this->executeExactPrepared('UPDATE tbl_grassguide_jobs SET due_at=?,last_error=? WHERE provider=? AND status IN (\'queued\',\'retry\') AND due_at<?',[$due,$error,$job['provider'],$due]);
        });
    }
    public function applyEnrichment(array $job,array $result): void
    {
        $this->getObject('grassguiderights','grassguide');
        $this->transaction(function() use($job,$result) {
            // Cross-provider workers serialize content merging, while HTTP runs outside transactions.
            $row=$this->selectExactPrepared('SELECT * FROM tbl_grassguide_species WHERE id=? FOR UPDATE',[$job['species_id']])[0] ?? null;
            if(!$row)throw new DomainException('missing');
            $data=json_decode($row['payload'],true,32,JSON_THROW_ON_ERROR);
            if($row['scientific_name']!==$result['name'])throw new DomainException('renamed_during_fetch');
            if($data['state']!=='archived') {
                $provider=$job['provider'];$before=$data;
                foreach($result['fields'] as $key=>$value) {
                    if(!empty($data['_manual_fields'][$key]))continue;
                    $owner=$data['_field_sources'][$key]['provider'] ?? null;
                    $previous=$data['_imported_values'][$key] ?? null;
                    $empty=($data[$key] ?? '')==='' || ($data[$key] ?? [])===[];
                    $ownedUnchanged=$owner!==null && ($data[$key] ?? null)===$previous;
                    $preferred=$provider==='grassbase' && in_array($owner,['wikipedia','gbif'],true) && in_array($key,['description','diagnostic','heads','awns','trait_source'],true);
                    if(!$empty && !($ownedUnchanged && ($owner===$provider || $preferred)))continue;
                    $data[$key]=$value;$data['_imported_values'][$key]=$value;$data['_field_sources'][$key]=$result['sources'][$key];
                }
                // Validate provider content through the same input contract as manual edits.
                $validated=grassguideinput::species($data);
                $data=array_merge($data,$validated);
                if($data!==$before)$this->writeSpecies($row['id'],(int)$row['revision'],$data,'grassguide-worker');
                foreach($result['media'] as $media) {
                    if(!empty($data['_block_nc_images'])&&grassguiderights::licence($media['licence']??'')==='nc')continue;
                    $id=md5($row['id'].':'.$media['external_id']);
                    // Idempotent creates; archived or edited imported photographs stay untouched.
                    if($this->mediaOne($id))continue;
                    $this->writeMedia($row['id'],$id,0,grassguideinput::media($media)+['_provider'=>$provider,'_external_id'=>$media['external_id']],'grassguide-worker');
                }
            }
            $this->executeExactPrepared('UPDATE tbl_grassguide_jobs SET status=?,result_json=?,last_error=\'\',updated_at=? WHERE id=?',[$result['matched']?'done':'unmatched',json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),gmdate('Y-m-d H:i:s'),$job['id']]);
        });
    }
}
