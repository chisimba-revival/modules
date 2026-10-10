<?php
/** Reusable public host profiles and adapters for existing published speakers.
 * @author Derek Keats <derek@dkeats.com>
 */
if(empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class hostservice extends ChisimbaObject
{
    public function canCreate()
    {
        $u=$this->getObject('user','security'); if(!$u->isLoggedIn()) return false;
        if($u->isAdmin()) return true;
        $p=$this->getObject('permissionservice','security');
        foreach(['host-service','events','webinar'] as $name) {
            $area=$p->areaIdForName('chisimba',$name); $right=$area?$p->rightIdForArea($area,'manage'):null;
            if($right&&$p->isGranted($u->userId(),$right)) return true;
        }
        return false;
    }
    private function rows()
    {
        $rows=$this->objEngine->getDbObj()->queryAll('SELECT * FROM tbl_host_service_profiles ORDER BY name',null,MDB2_FETCHMODE_ASSOC);
        if(!is_array($rows)) throw new RuntimeException('Host directory unavailable'); return $rows;
    }
    public function choices()
    {
        $result=[]; foreach($this->rows() as $r) $result[]=['id'=>'host:'.$r['id'],'name'=>$r['name']];
        foreach($this->speakers() as $r) $result[]=['id'=>'webinar:'.$r['id'],'name'=>$r['title']];
        usort($result,fn($a,$b)=>strcasecmp($a['name'],$b['name'])); return $result;
    }
    private function speakers()
    {
        if(!$this->getObject('modules','modulecatalogue')->checkIfRegistered('webinar')) return [];
        return $this->getObject('webinarstore','webinar')->published('speaker');
    }
    public function profile($reference)
    {
        if(!is_string($reference)) return null;
        foreach($this->rows() as $r) if($reference==='host:'.$r['id']) return ['name'=>$r['name'],'biography'=>$r['biography'],'image_url'=>$r['image_url']];
        foreach($this->speakers() as $r) if($reference==='webinar:'.$r['id']) {
            $d=json_decode($r['payload'],true,512,JSON_THROW_ON_ERROR);
            return ['name'=>$r['title'],'biography'=>html_entity_decode(strip_tags($d['description']??''),ENT_QUOTES,'UTF-8'),'image_url'=>$d['image']??''];
        }
        return null;
    }
    /** Create once using a caller-generated key so retries cannot duplicate hosts. */
    public function create(array $input)
    {
        if(!$this->canCreate()) throw new DomainException('forbidden');
        $values=[];
        foreach(['name'=>191,'biography'=>6000,'image_url'=>1000] as $key=>$max) {
            $value=$input[$key]??'';
            if(!is_string($value)||mb_strlen($value)>$max||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/',$value)) throw new DomainException('invalid_host_'.$key);
            $values[$key]=trim($value);
        }
        if($values['name']==='') throw new DomainException('invalid_host_name');
        $image=$values['image_url'];
        if($image!==''&&(!filter_var($image,FILTER_VALIDATE_URL)||!in_array(parse_url($image,PHP_URL_SCHEME),['https','http'],true))) throw new DomainException('invalid_host_image_url');
        $id=$input['create_id']??''; if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id)) throw new DomainException('session_expired');
        $owner=(string)$this->getObject('user','security')->userId();
        foreach($this->rows() as $r) if($r['id']===$id) {
            if($r['owner_id']!==$owner) throw new DomainException('forbidden'); return 'host:'.$id;
        }
        $db=$this->objEngine->getDbObj(); $quote=fn($v)=>$v===''?"''":$db->quote($v,'text');
        $row=['id'=>$id,'owner_id'=>$owner]+$values;
        $result=$db->exec('INSERT INTO tbl_host_service_profiles ('.implode(',',array_keys($row)).') VALUES ('.implode(',',array_map($quote,array_values($row))).')');
        if($result===false||PEAR::isError($result)) throw new RuntimeException('Host could not be saved');
        return 'host:'.$id;
    }
}
