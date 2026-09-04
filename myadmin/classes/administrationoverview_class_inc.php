<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class administrationoverview extends ChisimbaObject
{
 public function show(){
  $metrics=$this->getObject('sitemetrics');
  $users=$metrics->countUsers();
  $courses=$metrics->countCourses();
  $online=(int)$this->getObject('loggedinusers','security')->getActiveUserCount();
  $e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
  $stats=array(array('Users',$users,'Registered accounts'),array('Courses',$courses,'Courses on this site'),array('Online now',$online,'Currently active sessions'));
  $html='<section class="student-learning-overview site-health-overview" aria-labelledby="site-health-title"><header class="student-learning-overview__header"><div><p class="student-learning-overview__eyebrow">Site overview</p><h1 id="site-health-title">My Administration</h1><p>See the health and current activity of this site.</p></div><span class="student-learning-overview__count">Live</span></header><div class="site-health-grid">';
  foreach($stats as $stat)$html.='<article class="site-health-card"><span>'.$e($stat[0]).'</span><strong>'.$e(number_format($stat[1])).'</strong><p>'.$e($stat[2]).'</p></article>';
  return $html.'</div><section class="site-health-attention"><div><p class="student-learning-overview__eyebrow">Needs attention</p><h2>Nothing requiring action</h2><p>Administrative requests and service warnings will appear here as those services provide them.</p></div></section></section>';
 }
}
?>
