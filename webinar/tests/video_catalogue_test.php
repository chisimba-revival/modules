<?php
$GLOBALS['kewl_entry_point_run']=true;class dbTable{}
require dirname(__DIR__).'/classes/webinarvideos_class_inc.php';
foreach([[1,120,1],[20,120,20],[999,120,20],[1,0,1]] as [$page,$total,$expected])if(webinarvideos::page($page,$total)!==$expected)throw new RuntimeException('Paging boundary');
foreach([0,-1,[],"2 OR 1=1",'1.5'] as $bad){try{webinarvideos::page($bad,120);throw new LogicException('Invalid page accepted');}catch(InvalidArgumentException $expected){}}
echo "PASS: catalogue pagination boundaries and invalid request rejection\n";
