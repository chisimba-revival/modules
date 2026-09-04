<?php
/** A mixed-role account sees only courses it teaches on My Teaching. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public $services=array();public function getObject($name,$module=null){return $this->services[$name];}}
require dirname(__DIR__).'/classes/teachingcourseoverview_class_inc.php';
$overview=new teachingcourseoverview();
$overview->services=array(
 'user'=>new class {public function isLoggedIn(){return true;}public function userId(){return 'mixed-user';}},
 'usercontext'=>new class {public function getContextWhereLecturer($user){return array('teaching-b','teaching-a','teaching-a');}},
 'dbcontext'=>new class {public function getContextDetails($code){return array('title'=>$code==='teaching-a'?'Algebra':'Biology','status'=>'Published');}},
 'contextimage'=>new class {public function getContextImage($code){return $code==='teaching-a'?'/contextimage/teaching-a.jpg':false;}},
);
$overview->init();$courses=$overview->courses('mixed-user');
if(array_column($courses,'code')!==array('teaching-a','teaching-b'))throw new RuntimeException('Teaching audience leaked or duplicated');
if($courses[0]['image']!=='/contextimage/teaching-a.jpg')throw new RuntimeException('Teaching card lost its course image');
echo "PASS: My Teaching contains unique course-author relationships only.\n";
