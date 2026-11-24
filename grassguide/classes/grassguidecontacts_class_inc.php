<?php
/** Private contributor contacts and immutable, explicitly queued permission requests. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguidecontacts extends ChisimbaObject
{
    public function init() {}
    private function service() { return $this->getObject('grassguideservice','grassguide'); }
    private function store() { return $this->getObject('grassguidestore','grassguide'); }
    private function text(string $key): string { return str_replace('\\n',"\n",$this->getObject('grassguiderenderer','grassguide')->text($key)); }
    public function groups(): array
    {
        $this->service()->guard();$groups=[];
        foreach($this->getObject('grassguiderights','grassguide')->ncContributors() as $g)$groups[$g['id']]=$g;
        // Keep saved contacts and correspondence reachable after images are replaced/archived.
        foreach($this->store()->rightsAll() as $row)if(str_starts_with($row['target'],'contributor:')) {
            $d=grassguideservice::unpack($row);$id=$d['contributor_id'];
            if(!isset($groups[$id]))$groups[$id]=['id'=>$id,'creator'=>$d['creator'],'items'=>[],'species'=>[],'photos'=>[],'needs_permission'=>0];
        }
        $groups=array_values($groups);usort($groups,fn($a,$b)=>strnatcasecmp($a['creator'],$b['creator']));return $groups;
    }
    public function group(string $id): array
    {
        $this->service()->guard();
        foreach($this->groups() as $g)if(hash_equals($g['id'],$id))return $g;
        throw new DomainException('rights_missing');
    }
    public function contact(string $id): array
    {
        $g=$this->group($id);$row=$this->store()->rightsOne('contributor:'.$id);
        return $row?grassguideservice::unpack($row):['revision'=>0,'contributor_id'=>$id,'email'=>'','opening'=>sprintf($this->text('default_opening'),$g['creator']),'subject'=>$this->text('default_subject'),'request_body'=>$this->text('default_request'),'signature'=>$this->getObject('user','security')->fullname()];
    }
    public function save(string $id,array $input): array
    {
        $g=$this->group($id);$revision=$this->revision($input);
        $data=['contributor_id'=>$id,'creator'=>$g['creator']];
        foreach(['email'=>320,'opening'=>3000,'subject'=>200,'request_body'=>8000,'signature'=>500] as $key=>$limit)$data[$key]=grassguideinput::text($input,$key,$limit,in_array($key,['subject','request_body','signature'],true));
        if($data['email']!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL))throw new DomainException('contact_email_invalid');
        if(preg_match('/[\r\n]/',$data['subject']))throw new DomainException('invalid');
        return grassguideservice::unpack($this->store()->writeRights('contributor:'.$id,$revision,$data,$this->service()->actor(),str_repeat('0',32)));
    }
    private function revision(array $input): int
    {
        $v=$input['revision']??'';
        if(!is_scalar($v)||!preg_match('/^(0|[1-9][0-9]{0,8})$/D',(string)$v))throw new DomainException('invalid');
        return (int)$v;
    }
    public function sender(): array
    {
        $this->service()->guard();$settings=$this->getObject('dbsysconfig','sysconfig');
        $name=trim((string)$settings->getValue('COMMUNICATION_FROM_NAME','communications'));
        if($name===''||strcasecmp($name,'Chisimba')===0)$name=$this->getObject('altconfig','config')->getSiteName();
        return ['name'=>$name,'email'=>trim((string)$settings->getValue('COMMUNICATION_FROM_EMAIL','communications')),'transport'=>strtolower(trim((string)$settings->getValue('COMMUNICATION_TRANSPORT','communications')))];
    }
    public function preview(string $id): array
    {
        $g=$this->group($id);$d=$this->contact($id);$sender=$this->sender();$photos=[];
        foreach($g['items'] as $item) {
            if(grassguiderights::eligible($item,'commercial'))continue;
            // Each rights record remains covered even when a photo serves several species.
            $photos[]=['target'=>$item['target'],'fingerprint'=>$item['fingerprint'],'name'=>$item['name'],'source_url'=>$item['source_url'],'image_url'=>$item['content']['image_url']];
        }
        $body=trim($d['opening'])."\n\n".trim($d['request_body'])."\n\n".$this->text('request_photos')."\n";
        foreach($photos as $i=>$photo)$body.=($i+1).'. '.$photo['name']."\n".$photo['source_url']."\n".$photo['image_url']."\n\n";
        $body.=trim($d['signature']);
        $preview=['contributor_id'=>$id,'creator'=>$g['creator'],'revision'=>$d['revision'],'to'=>$d['email'],'subject'=>$d['subject'],'body'=>$body,'sender'=>$sender,'photos'=>$photos];
        $preview['fingerprint']=hash('sha256',json_encode($preview,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
        return $preview;
    }
    public function queue(string $id,array $input): array
    {
        $this->service()->guard();$request=grassguideinput::text($input,'request_id',32,true);$fingerprint=grassguideinput::text($input,'fingerprint',64,true);
        if(!preg_match('/^[a-f0-9]{32}$/D',$request)||!preg_match('/^[a-f0-9]{64}$/D',$fingerprint))throw new DomainException('invalid');
        return $this->store()->transactionWithSavepoint(function()use($id,$input,$request,$fingerprint){
            $existing=$this->store()->rightsOne('request:'.$request);
            if($existing) {
                $d=grassguideservice::unpack($existing);
                if(($d['contributor_id']??'')!==$id||!hash_equals($d['fingerprint'],$fingerprint))throw new DomainException('conflict');
                return $d; // A retried submission cannot create a second outbox item.
            }
            foreach($this->history($id) as $previous)if($previous['delivery_status']==='queued')throw new DomainException('permission_already_pending');
            $p=$this->preview($id);
            if($this->revision($input)!==(int)$p['revision']||!hash_equals($p['fingerprint'],$fingerprint))throw new DomainException('conflict');
            if(!$p['revision']||!filter_var($p['to'],FILTER_VALIDATE_EMAIL))throw new DomainException('contact_email_invalid');
            if(!$p['photos'])throw new DomainException('no_permission_images');
            if(!filter_var($p['sender']['email'],FILTER_VALIDATE_EMAIL)||$p['sender']['transport']!=='sendgrid')throw new DomainException('permission_mail_unavailable');
            $contact=$this->store()->rightsOne('contributor:'.$id);
            $data=json_decode($contact['payload'],true,32,JSON_THROW_ON_ERROR);
            // The revision update serialises competing submissions before any queue insert.
            $this->store()->writeRights('contributor:'.$id,(int)$p['revision'],$data,$this->service()->actor(),str_repeat('0',32));
            $queued=$this->getObject('communicationservice','communications')->queueEmail(['to'=>$p['to'],'toName'=>mb_substr($p['creator'],0,255),'subject'=>$p['subject'],'text'=>$p['body'],'idempotencyKey'=>'grassguide-permission:'.$request,'metadata'=>['module'=>'grassguide','purpose'=>'image-permission','request_id'=>$request]]);
            if(empty($queued['ok']))throw new DomainException('permission_queue_failed');
            $p['message_id']=$queued['messageId'];$p['queued_by']=$this->service()->actor();$p['queued_at']=gmdate('Y-m-d H:i:s');
            return grassguideservice::unpack($this->store()->writeRights('request:'.$request,0,$p,$this->service()->actor(),str_repeat('0',32)));
        });
    }
    public function history(string $id): array
    {
        $this->group($id);$rows=[];
        foreach($this->store()->rightsAll() as $row)if(str_starts_with($row['target'],'request:')) {
            $d=grassguideservice::unpack($row);if(($d['contributor_id']??'')!==$id)continue;
            $status=$this->getObject('communicationservice','communications')->status($d['message_id']);
            $d['delivery_status']=$status['status']??'unknown';$rows[]=$d;
        }
        usort($rows,fn($a,$b)=>strcmp($b['queued_at'],$a['queued_at']));return $rows;
    }
}
