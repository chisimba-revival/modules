<?php
$v=$this->getObject('sharingview','workgroup');echo $v->start($creating?'creategroup':'editgroup',$error,'manage');
if($this->getParam('saved')==='1')echo '<p role="status">'.$v->t('saved').'</p>';
$params=['action'=>$creating?'createconfirm':'processform'];if(!$creating)$params['workgroupId']=$draft['id']??'';
echo '<form method="post" action="'.$v->url($params,'workgroupadmin').'">'.$v->hidden('csrf_token',$csrfToken);
foreach(['id','revision'] as $key)echo $v->hidden($key,$draft[$key]??'');
echo $v->input('description','groupname',$draft['description']??'',100,true);
echo '<fieldset><legend>'.$v->t('members').'</legend><p>'.$v->t('memberhelp').'</p>';
$selected=is_array($draft['members']??null)?$draft['members']:[];
foreach($candidates as $id=>$name)echo '<label><input type="checkbox" name="members[]" value="'.$v->e($id).'"'.(in_array($id,$selected,true)?' checked':'').'> '.$v->e($name).'</label><br>';
echo '</fieldset><div class="chisimba-form-actions"><button type="submit">'.$v->t('save').'</button>'.$v->link([],'back','arrow-left','workgroupadmin').'</div></form></section>';
