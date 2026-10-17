<?php
$v=$this->getObject('sharingview','workgroup');
echo $v->start('groups',$error);
echo '<p>'.$v->t('choose').'</p>';
if($service->manager($service->context()))echo $v->link([],'manage','users','workgroupadmin');
if(!$groups)echo '<p>'.$v->t('emptygroups').'</p>';
echo '<ul>';
foreach($groups as $row)echo '<li><a href="'.$v->url(['action'=>'joinworkgroup','workgroup'=>$row['id']]).'">'.$v->e($row['description']).'</a></li>';
echo '</ul></section>';
