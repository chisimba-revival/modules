<?php
$v=$this->getObject('sharingview','workgroup');echo $v->start('files',$error);
echo '<div class="chisimba-form-actions">'.$v->link(['action'=>'leaveworkgroup'],'groups').'</div></section>';
