<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class tierpresentationservice extends ChisimbaObject
{
    private const TIERS=array('free','tier_1','tier_2');
    public function init(){ $this->store=$this->getObject('dbpaymenttiercontent'); }
    public function all(){
        $defaults=$this->defaults();$result=array();
        foreach(self::TIERS as $tier){$saved=$this->store->byTier($tier);$result[$tier]=array_merge($defaults[$tier],is_array($saved)?array_filter(array('summary'=>$saved['summary']??null,'features'=>$saved['features']??null),static fn($v)=>$v!==null):array());}
        return $result;
    }
    public function save(array $input){
        foreach(self::TIERS as $tier){$summary=$this->plain($input[$tier.'_summary']??'',500);$features=$this->features($input[$tier.'_features']??'');if($summary===null||$features===null)return array('ok'=>false,'code'=>'invalid_tier_content');if(!$this->store->saveTier($tier,array('summary'=>$summary,'features'=>$features)))return array('ok'=>false,'code'=>'tier_content_failed');}
        return array('ok'=>true,'code'=>'tier_content_saved');
    }
    private function defaults(){return array(
        'free'=>array('summary'=>'Start learning with courses available to every registered learner.','features'=>"Access to all Free courses\nNo membership payment\nUpgrade whenever you are ready"),
        'tier_1'=>array('summary'=>'Go further with specialist learning for active members.','features'=>"Everything in Free\nAccess to Tier 1 courses\nMembership learning as it is published"),
        'tier_2'=>array('summary'=>'Unlock the complete membership learning collection.','features'=>"Everything in Tier 1\nAccess to Tier 2 courses\nOur highest membership level"),
    );}
    private function plain($value,$max){$value=trim(strip_tags((string)$value));return $value!==''&&strlen($value)<=$max&&!preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value)?$value:null;}
    private function features($value){$lines=preg_split('/\R/u',(string)$value);$clean=array();foreach((array)$lines as $line){$line=$this->plain($line,191);if($line!==null)$clean[]=$line;if(count($clean)>12)return null;}return $clean?implode("\n",$clean):null;}
}
?>
