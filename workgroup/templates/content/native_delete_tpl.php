<?php
$v=$this->getObject('sharingview','workgroup');echo $v->start('remove',$error);
echo '<p>'.$v->e($deleting['title']).'</p><p>'.$v->t('removehelp').'</p>';
echo '<form method="post" action="'.$v->url(['action'=>'deletefile','fileid'=>$deleting['id'],'workgroupid'=>$group['id']]).'">'
    .$v->hidden('csrf_token',$csrfToken).$v->hidden('revision',$service::revision($deleting)).$v->hidden('confirm','yes')
    .'<div class="chisimba-form-actions"><button type="submit">'.$v->t('confirmremove').'</button>'.$v->link(['workgroupid'=>$group['id']],'cancel').'</div></form></section>';
