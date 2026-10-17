<?php
$v=$this->getObject('sharingview','workgroup');echo $v->start('manage',$error,'manage');
echo '<div class="chisimba-form-actions">'.$v->link(['action'=>'create'],'creategroup','plus','workgroupadmin').$v->link(['action'=>'leaveworkgroup'],'files').'</div>';
if(!$groups)echo '<p>'.$v->t('emptygroups').'</p>';
foreach($groups as $row)echo '<article class="chisimba-form-card"><h2>'.$v->e($row['description']).'</h2><div class="chisimba-form-actions">'
    .$v->link(['action'=>'manage','workgroupId'=>$row['id']],'edit','pencil','workgroupadmin')
    .$v->link(['action'=>'delete','workgroupId'=>$row['id']],'remove','trash-2','workgroupadmin').'</div></article>';
echo '</section>';
