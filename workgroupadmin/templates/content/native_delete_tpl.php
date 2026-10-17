<?php
$v=$this->getObject('sharingview','workgroup');echo $v->start('removegroup',$error,'manage');
echo '<p>'.$v->e($group['description']).'</p><p>'.$v->t('removegrouphelp').'</p>';
echo '<form method="post" action="'.$v->url(['action'=>'delete','workgroupId'=>$group['id']],'workgroupadmin').'">'
    .$v->hidden('csrf_token',$csrfToken).$v->hidden('revision',$service->groupRevision($group)).$v->hidden('confirm','yes')
    .'<div class="chisimba-form-actions"><button type="submit">'.$v->t('confirmremove').'</button>'.$v->link([],'cancel','arrow-left','workgroupadmin').'</div></form></section>';
