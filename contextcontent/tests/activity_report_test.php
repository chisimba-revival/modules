<?php
/** Report validation, query boundaries and access regression. @author Derek Keats */
error_reporting(E_ALL);
set_error_handler(function($severity,$message){throw new ErrorException($message,0,$severity);});
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {
    public array $calls=[];
    public bool $allowed=true;
    public bool $admin=true;
    public bool $lecturer=false;
    public function getObject($name,$module=null) {
        return match($name) {
            'user'=>new class($this->allowed,$this->admin) {function __construct(private $allowed,private $admin){} function isLoggedIn(){return $this->allowed;} function isAdmin(){return $this->admin;}},
            'managegroups'=>new class($this->lecturer) {function __construct(private $lecturer){} function isContextLecturer(){return $this->lecturer;}},
            'dbcontext'=>new class {function getContextCode(){return 'course-a';}},
            'groupservice'=>new class {function groupIdForName($name){if($name!=='course-a^Students')throw new Exception('Wrong group');return 1;} function getMembers($id){return [['userId'=>'student1']];}},
            default=>throw new Exception('Unexpected dependency: '.$name)
        };
    }
    public function selectPrepared($sql,$params) {$this->calls[]=[$sql,$params];return str_starts_with($sql,'SELECT COUNT')?[['total'=>61]]:[];}
}
require dirname(__DIR__).'/classes/contentactivityreport_class_inc.php';
$r=new contentactivityreport();
foreach ([['startdate'=>'2026-02-30'],['startdate'=>"2026-01-01' OR 1=1"],['startdate'=>'2026-10-14','enddate'=>'2026-10-01']] as $input) {
    try {$r->filters($input);throw new Exception('Invalid dates accepted');}catch(InvalidArgumentException $expected){}
}
$f=$r->filters(['startdate'=>'2026-10-01','enddate'=>'2026-10-14','q'=>"O'Neil",'page'=>2]);
$out=$r->report('course-a',$f,true);
if ($out['total']!==61 || $out['page']!==2) throw new Exception('Count or page lost');
foreach($r->calls as [$sql,$params]) {
    if(!str_contains($sql,'a.contextcode = ?') || str_contains($sql,"O'Neil") || !in_array('student1',$params,true) || !in_array('2026-10-15 00:00:00',$params,true))throw new Exception('Unscoped or unbound query');
}
if(!str_contains($r->calls[1][0],'LIMIT 30 OFFSET 30'))throw new Exception('Paging lost');
foreach(['root','course-b',''] as $scope){try{$r->report($scope,$f,false);throw new Exception('Scope accepted');}catch(RuntimeException $expected){}}
$r->admin=false;try{$r->report('course-a',$f,false);throw new Exception('Student accepted');}catch(RuntimeException $expected){}
$r->lecturer=true;$r->report('course-a',$f,false);
$r->allowed=false;try{$r->report('course-a',$f,false);throw new Exception('Anonymous accepted');}catch(RuntimeException $expected){}
if($r->csvCell('=SUM(A1)')!=="'=SUM(A1)" || $r->csvCell('bird, "tree"')!=='bird, "tree"')throw new Exception('CSV changed data or accepted formula');
echo "PASS: dates, scoped bound queries, current student filter, pagination, anonymous/cross-course denial and CSV formula protection\n";
