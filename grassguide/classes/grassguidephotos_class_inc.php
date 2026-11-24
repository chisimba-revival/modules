<?php
/** Species photo uploads use File Manager storage and the catalogue publication boundary. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguidephotos extends ChisimbaObject
{
    private function service() { return $this->getObject('grassguideservice','grassguide'); }
    private function store() { return $this->getObject('grassguidestore','grassguide'); }
    public function mayStoreFiles(): bool { return $this->service()->allowed(); }
    public function mayDownloadFile($file,$resource): bool
    {
        $row=$this->store()->mediaOne((string)$resource);
        if(!$row)return false;
        $m=grassguideservice::unpack($row);
        if(($m['_file_id']??'')!==$file['id'])return false;
        if($this->service()->allowed())return true;
        if($m['state']!=='active')return false;
        try { $bundle=$this->getObject('grassguiderights','grassguide')->publicExport(); }
        catch(DomainException $e) { return false; }
        foreach($bundle['species'] as $s)if($s['id']===$m['species_id'])foreach($s['media'] as $image)if($image['image_url']===$m['image_url'])return true;
        return false;
    }
    public function read(string $id): string
    {
        $row=$this->store()->mediaOne($id);$m=$row?grassguideservice::unpack($row):[];
        if(empty($m['_file_id']))throw new DomainException('missing');
        return $this->getObject('modulefilestore','filemanager')->readMedia('grassguide','grassguidephotos',$id,$m['_file_id']);
    }
    public static function captions(string $csv,int $count): array
    {
        $stream=fopen('php://temp','w+');fwrite($stream,$csv);rewind($stream);
        try{$values=fgetcsv($stream,0,',','"','');$extra=fgetcsv($stream,0,',','"','');}finally{fclose($stream);}
        if(!is_array($values)||count($values)!==$count||$extra!==false)throw new DomainException('caption_count');
        return array_map(fn($v)=>grassguideinput::text(['caption'=>$v],'caption',1000,true),$values);
    }
    public function upload(string $species,array $input,array $files): array
    {
        $this->service()->guard();
        if(!isset($files['tmp_name'])||!is_array($files['tmp_name'])||count($files['tmp_name'])<1||count($files['tmp_name'])>10)throw new DomainException('photo_files');
        $photos=[];$total=0;
        foreach($files['tmp_name'] as $i=>$path){
            if(!is_string($path)||($files['error'][$i]??null)!==UPLOAD_ERR_OK||!is_uploaded_file($path))throw new DomainException('photo_files');
            $size=filesize($path);$total+=$size;
            if($size>10485760||$total>41943040)throw new DomainException('photo_files');
            $bytes=file_get_contents($path);if($bytes===false)throw new DomainException('photo_files');
            $photos[]=$bytes;
        }
        return $this->saveBatch($species,$input,$photos);
    }
    /** Also used by isolated integration tests; every caller still requires administrator rights. */
    public function saveBatch(string $species,array $input,array $photos): array
    {
        $this->service()->guard();
        $current=$this->service()->one($species);
        if(!$current||$current['state']==='archived')throw new DomainException('missing');
        if((string)$current['revision']!==($input['revision']??null))throw new DomainException('conflict');
        if(count($photos)<1||count($photos)>10)throw new DomainException('photo_files');
        $captions=self::captions(grassguideinput::text($input,'captions',12000,true),count($photos));
        $creator=grassguideinput::text($input,'creator',300,true);
        $licence=grassguideinput::choice($input,'licence',['CC-BY-4.0','CC0-1.0','CC-BY-SA-4.0'],false);
        $mode=grassguideinput::choice($input,'photo_mode',['add','replace_nc'],false);
        $files=$this->getObject('modulefilestore','filemanager');$total=0;
        foreach($photos as $bytes){
            try{$type=$files::mediaType($bytes);}catch(InvalidArgumentException $e){throw new DomainException('photo_files');}
            $total+=strlen($bytes);if($type['category']!=='images'||$total>41943040)throw new DomainException('photo_files');
        }
        $created=[];$media=[];
        try {
            foreach($photos as $i=>$bytes){
                $file=$files->storeMedia('grassguide','grassguidephotos',$bytes);$created[]=$file;
                $id=bin2hex(random_bytes(16));
                $url=$this->uri(['action'=>'photo','id'=>$id],'grassguide');
                $url=grassguideinput::url(['url'=>$url],'url',true);
                $media[$id]=grassguideinput::media(['kind'=>'image','role'=>'other','state'=>'active','source_url'=>$url,'image_url'=>$url,'caption'=>$captions[$i],'alt_text'=>$captions[$i],'creator'=>$creator,'licence'=>$licence,'rights_note'=>''])+['_file_id'=>$file];
            }
            return grassguideservice::unpack($this->store()->writePhotoBatch($species,(int)$current['revision'],$media,$mode,$this->service()->actor()));
        } catch(Throwable $e) {
            foreach($created as $id)try{$files->discard('grassguide','grassguidephotos',$id);}catch(Throwable $cleanup){error_log('Grass guide unlinked photo cleanup failed.');}
            throw $e;
        }
    }
    public function resume(string $species,array $input): array
    {
        $this->service()->guard();$current=$this->service()->one($species);
        if(!$current)throw new DomainException('missing');
        if((string)$current['revision']!==($input['revision']??null))throw new DomainException('conflict');
        return grassguideservice::unpack($this->store()->writePhotoBatch($species,(int)$current['revision'],[],'resume',$this->service()->actor()));
    }
}
