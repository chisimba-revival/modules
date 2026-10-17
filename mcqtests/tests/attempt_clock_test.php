<?php
/** Timed attempts use persisted UTC timestamps, never submitted remaining time. */
$GLOBALS['kewl_entry_point_run']=true;
class controller {
    public function getObject($name,$module){return new class {
        public function parseStorage($value){return $value?new DateTimeImmutable($value,new DateTimeZone('UTC')):null;}
        public function nowUtc(){return new DateTimeImmutable('2026-10-17 10:00:00',new DateTimeZone('UTC'));}
    };}
}
require dirname(__DIR__).'/controller.php';
$c=new mcqtests();$method=new ReflectionMethod($c,'attemptSecondsRemaining');
foreach(array(
 array(array('timed'=>0),false,PHP_INT_MAX),
 array(array('timed'=>1,'duration'=>30),array('starttime'=>'2026-10-17 09:45:00'),900),
 array(array('timed'=>1,'duration'=>30),array('starttime'=>'2026-10-17 09:00:00'),-1800),
 array(array('timed'=>1,'duration'=>30),false,-16)
) as $case) {
 if ($method->invoke($c,$case[0],$case[1])!==$case[2])throw new RuntimeException('Attempt clock mismatch');
}
echo "PASS persisted attempt deadlines, expiry and missing-start fail closed\n";
