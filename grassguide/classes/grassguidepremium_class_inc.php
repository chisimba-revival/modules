<?php
/** Account grants use Entitlement Service; device credentials use Security's scoped API keys. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class grassguidepremium extends ChisimbaObject
{
    public const SCOPE='grassguide:premium';
    public function entitlement(string $userId): array
    {
        $user=$this->getObject('userservice','security')->findByUserId($userId);
        if(!$user||(int)($user['isactive']??0)!==1)throw new DomainException('forbidden');
        $expiry=0;$premium=false;$lifetime=false;
        foreach($this->getObject('entitlementservice','entitlement-service')->activeForUser($userId,null,500) as $grant){
            if($grant['entitlement_type']!=='premium'||$grant['resource_type']!=='app'||$grant['resource_id']!=='lowveld-grasses')continue;
            $premium=true;
            if(empty($grant['expires_at']))$lifetime=true;
            else $expiry=max($expiry,strtotime($grant['expires_at'].' UTC'));
        }
        return ['user_id'=>$userId,'username'=>(string)$user['username'],'premium'=>$premium,'lifetime'=>$premium&&$lifetime,'offline_until'=>$premium?min(time()+30*86400,$lifetime?PHP_INT_MAX:$expiry):0];
    }
    private function owner(): string
    {
        $this->getObject('grassguideservice','grassguide')->guard();
        $id=(string)$this->getObject('user','security')->userId();
        if(!$this->entitlement($id)['premium'])throw new DomainException('premium_required');
        return $id;
    }
    public function keys(): array
    {
        $id=$this->owner();$rows=$this->getObject('tokenauthservice','security')->listApiKeys($id);
        return array_values(array_filter($rows,fn($row)=>json_decode($row['scopes']??'[]',true)===[self::SCOPE]));
    }
    public function create(array $input): array
    {
        $id=$this->owner();$name=grassguideinput::text($input,'device_name',70,true);
        if(count($this->keys())>=20)throw new DomainException('premium_key_limit');
        return $this->getObject('tokenauthservice','security')->createApiKey($id,'Grass app: '.$name,[self::SCOPE]);
    }
    public function revoke(array $input): void
    {
        $id=$this->owner();$key=grassguideinput::text($input,'key_id',20,true);
        foreach($this->keys() as $row)if((string)$row['id']===$key){
            if(!$this->getObject('tokenauthservice','security')->revokeApiKey($id,(int)$key))throw new DomainException('conflict');
            return;
        }
        throw new DomainException('missing');
    }
    public function sightingsResponse(string $method,string $key,string $body): array
    {
        $headers=['Content-Type'=>'application/json; charset=UTF-8','Cache-Control'=>'no-store','Access-Control-Allow-Origin'=>'*','X-Content-Type-Options'=>'nosniff'];
        if($method==='OPTIONS')return ['status'=>204,'headers'=>$headers+['Access-Control-Allow-Methods'=>'POST, OPTIONS','Access-Control-Allow-Headers'=>'X-API-Key, Content-Type'],'body'=>''];
        if($method!=='POST')return ['status'=>405,'headers'=>$headers+['Allow'=>'POST, OPTIONS'],'body'=>'{"error":"method"}'];
        $auth=$this->response('GET',$key);if($auth['status']!==200)return $auth;
        $account=json_decode($auth['body'],true);if(!$account['premium'])return ['status'=>403,'headers'=>$headers,'body'=>'{"error":"premium_required"}'];
        try{
            if(strlen($body)>65536)throw new DomainException('invalid');
            $input=json_decode($body,true,32,JSON_THROW_ON_ERROR);$records=$input['sightings']??null;
            if(!is_array($records)||!array_is_list($records)||count($records)>100)throw new DomainException('invalid');
            $clean=[];foreach($records as $record){
                if(!is_array($record))throw new DomainException('invalid');
                foreach(['id','species_id'] as $field)if(!is_string($record[$field]??null)||!preg_match('/^[a-f0-9]{32}$/D',$record[$field]))throw new DomainException('invalid');
                if(!is_int($record['observed_at']??null)||$record['observed_at']<946684800||$record['observed_at']>time()+86400)throw new DomainException('invalid');
                $location=$record['location']??null;
                if($location!==null){
                    if(!is_array($location))throw new DomainException('invalid');
                    foreach(['latitude'=>90,'longitude'=>180,'accuracy'=>100000] as $field=>$limit)if(!is_numeric($location[$field]??null)||!is_finite((float)$location[$field])||abs((float)$location[$field])>$limit||($field==='accuracy'&&$location[$field]<0))throw new DomainException('invalid');
                    $location=array_map(fn($v)=>(float)$v,array_intersect_key($location,array_flip(['latitude','longitude','accuracy'])));
                }
                $clean[]=['id'=>$record['id'],'species_id'=>$record['species_id'],'list'=>grassguideinput::text($record,'list',100,true),'notes'=>grassguideinput::text($record,'notes',1000),'observed_at'=>$record['observed_at'],'location'=>$location];
            }
            $data=$this->getObject('grassguidestore','grassguide')->syncSightings($account['user_id'],$clean);
            return ['status'=>200,'headers'=>$headers,'body'=>json_encode($data,JSON_THROW_ON_ERROR)];
        }catch(DomainException|JsonException $e){return ['status'=>422,'headers'=>$headers,'body'=>'{"error":"invalid_sighting"}'];}
    }
    public function checklistResponse(string $method,string $key,string $body): array
    {
        $headers=['Content-Type'=>'application/json; charset=UTF-8','Cache-Control'=>'no-store','Access-Control-Allow-Origin'=>'*','X-Content-Type-Options'=>'nosniff'];
        if($method==='OPTIONS')return ['status'=>204,'headers'=>$headers+['Access-Control-Allow-Methods'=>'POST, OPTIONS','Access-Control-Allow-Headers'=>'X-API-Key, Content-Type'],'body'=>''];
        if($method!=='POST')return ['status'=>405,'headers'=>$headers+['Allow'=>'POST, OPTIONS'],'body'=>'{"error":"method"}'];
        $auth=$this->response('GET',$key);
        if($auth['status']!==200)return $auth;
        $account=json_decode($auth['body'],true);
        if(!$account['premium'])return ['status'=>403,'headers'=>$headers,'body'=>'{"error":"premium_required"}'];
        try {
            if(strlen($body)>32768)throw new DomainException('request');
            $input=json_decode($body,true,32,JSON_THROW_ON_ERROR);
            if(!is_array($input)||!is_string($input['device']??null)||!preg_match('/^[a-f0-9]{32}$/D',$input['device'])||!is_array($input['operations']??null)||!array_is_list($input['operations'])||count($input['operations'])>100)throw new DomainException('request');
            foreach($input['operations'] as $op)if(!is_array($op)||!is_int($op['seq']??null)||$op['seq']<1||$op['seq']>2147483647||!is_string($op['id']??null)||!preg_match('/^[a-f0-9]{32}$/D',$op['id'])||!is_bool($op['checked']??null))throw new DomainException('request');
            $data=$this->getObject('grassguidestore','grassguide')->syncChecklist($account['user_id'],$input['device'],$input['operations']);
            return ['status'=>200,'headers'=>$headers,'body'=>json_encode($data,JSON_THROW_ON_ERROR)];
        }catch(DomainException|JsonException $e){return ['status'=>422,'headers'=>$headers,'body'=>'{"error":"invalid_checklist"}'];}
    }
    public function response(string $method,string $key): array
    {
        $headers=['Content-Type'=>'application/json; charset=UTF-8','Cache-Control'=>'no-store','Access-Control-Allow-Origin'=>'*','X-Content-Type-Options'=>'nosniff'];
        if($method==='OPTIONS')return ['status'=>204,'headers'=>$headers+['Access-Control-Allow-Methods'=>'GET, OPTIONS','Access-Control-Allow-Headers'=>'X-API-Key'],'body'=>''];
        if($method!=='GET')return ['status'=>405,'headers'=>$headers+['Allow'=>'GET, OPTIONS'],'body'=>'{"error":"method"}'];
        $credential=strlen($key)<=256&&$key!==''?$this->getObject('tokenauthservice','security')->validateApiKeyDetailed($key):null;
        if(!$credential||$credential['scopes']!==[self::SCOPE])return ['status'=>401,'headers'=>$headers,'body'=>'{"error":"activation_required"}'];
        try{$data=$this->entitlement($credential['user_id']);}
        catch(DomainException $e){return ['status'=>403,'headers'=>$headers,'body'=>'{"error":"unavailable"}'];}
        return ['status'=>200,'headers'=>$headers,'body'=>json_encode($data,JSON_THROW_ON_ERROR)];
    }
}
