<?php
/** Help permission and language coverage; no learner data or network. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public function getObject($name,$module=null) { return $GLOBALS['services'][$name]; }
}
require dirname(__DIR__).'/classes/helpcontent_class_inc.php';
$texts=[];
foreach (file(dirname(__DIR__).'/register.conf') as $line) {
    if (preg_match('/^TEXT: mod_spokenassessment_([^|]+)\|[^|]*\|(.*)$/',$line,$m)) $texts[$m[1]]=$m[2];
}
$GLOBALS['services']=[
    'user'=>new class { public function userId(){return 'fixture';} },
    'dbcontext'=>new class { public function getContextCode(){return 'course';} },
    'spokenpolicy'=>new class {
        public $member=true,$teacher=false;
        public function member($id,$context){return $this->member;}
        public function teacher($id,$context){return $this->teacher;}
    },
    'spokenrenderer'=>new class {
        public function text($key){
            if (!isset($GLOBALS['texts'][$key])) throw new RuntimeException('Missing language item: '.$key);
            return $GLOBALS['texts'][$key];
        }
    }
];
function verify($condition,$message){if (!$condition) throw new RuntimeException($message);}
$help=new helpcontent();$policy=$GLOBALS['services']['spokenpolicy'];
$learner=$help->getTopic('practice');
verify(count($learner['steps'])===4 && count($learner['sections'])===5,'Learner journey and troubleshooting available');
$policy->teacher=true;$teacher=$help->getTopic('practice');
verify(count($teacher['sections'])===8,'Teaching guidance included for teaching team');
verify(array_slice($teacher['sections'],0,5)===$learner['sections'],'Shared guidance consistent');
verify($help->getTopic('unknown')===null,'Unknown topic denied');
$policy->member=false;
verify($help->getTopic('practice')===null,'Non-member cannot obtain course help');
echo "PASS contextual help access, role guidance and language coverage.\n";
