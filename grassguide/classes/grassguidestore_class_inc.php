<?php
/** All persistence uses the shared checked dbTable boundary. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguidestore extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') { parent::init('tbl_grassguide_species', $pearDb, $errorCallback); }
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
        return $this->transaction(function () use ($id, $revision, $data, $actor, $sourceKey) {
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
        return $this->transaction(function () use ($species,$id,$revision,$data,$actor) {
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
}
