<?php
$v=$this->getObject('sharingview','workgroup');echo $v->start('files',$error);
echo '<h2>'.$v->e($group['description']).'</h2>';
echo '<div class="chisimba-form-actions">'.$v->link(['action'=>'upload','workgroupid'=>$group['id']],'share','plus')
    .$v->link(['workgroupid'=>$group['id']],'refresh','refresh-cw').$v->link(['action'=>'leaveworkgroup'],'groups').'</div>';
if($this->getParam('saved')==='1')echo '<p role="status">'.$v->t('saved').'</p>';
echo '<p>'.$v->t('permissions').'</p>';
if(!$files)echo '<p>'.$v->t('emptyfiles').'</p>';
foreach($files as $row) {
    echo '<article class="chisimba-form-card"><h3>'.$v->e($row['title']).'</h3><p>'.$v->e($row['description']).'</p>';
    if((string)$row['version']!=='')echo '<p>'.$v->t('version').': '.$v->e($row['version']).'</p>';
    echo '<div class="chisimba-form-actions">';
    if($row['file'])echo $v->link(['action'=>'file','id'=>$row['fileid'],'filename'=>$row['file']['filename']],'download','download','filemanager');
    else echo '<p>'.$v->t('unavailable').'</p>';
    if($row['editable'])echo $v->link(['action'=>'editfile','fileid'=>$row['id'],'workgroupid'=>$group['id']],'edit','pencil')
        .$v->link(['action'=>'deletefile','fileid'=>$row['id'],'workgroupid'=>$group['id']],'remove','trash-2');
    echo '</div></article>';
}
echo '</section>';
