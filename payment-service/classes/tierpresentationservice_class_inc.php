<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class tierpresentationservice extends ChisimbaObject
{
    public function init(){ $this->store=$this->getObject('dbpaymenttiercontent'); }
    public function all(){
        $defaults=$this->defaults();$result=array();
        foreach(array_keys($this->getObject('membershipservice','membership-service')->tiers(true)) as $tier){$saved=$this->store->byTier($tier);$result[$tier]=array_merge(($defaults[$tier]??array('summary'=>'','features'=>'')),is_array($saved)?array_filter(array('summary'=>$saved['summary']??null,'features'=>$saved['features']??null),static fn($v)=>$v!==null):array());}
        return $result;
    }
    public function save(array $input){
        foreach(array_keys($this->getObject('membershipservice','membership-service')->tiers(true)) as $tier){$summary=$this->plain($input[$tier.'_summary']??'',500);$features=$this->features($input[$tier.'_features']??'');if($summary===null||$features===null)return array('ok'=>false,'code'=>'invalid_tier_content');if(!$this->store->saveTier($tier,array('summary'=>$summary,'features'=>$features)))return array('ok'=>false,'code'=>'tier_content_failed');}
        return array('ok'=>true,'code'=>'tier_content_saved');
    }
    private function defaults(){return json_decode(file_get_contents(dirname(__DIR__).'/resources/tier-content-defaults.json'),true) ?: array();}
    private function plain($value,$max){$value=trim(strip_tags((string)$value));return $value!==''&&strlen($value)<=$max&&!preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value)?$value:null;}
    private function features($value){$lines=preg_split('/\R/u',(string)$value);$clean=array();foreach((array)$lines as $line){$line=$this->plain($line,191);if($line!==null)$clean[]=$line;if(count($clean)>12)return null;}return $clean?implode("\n",$clean):null;}
}
?>
