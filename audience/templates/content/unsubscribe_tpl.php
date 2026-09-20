<?php
$r=$this->getObject('audiencerenderer','audience');$e=['audiencerenderer','escape'];$error=$this->getVar('audienceError');
echo '<section class="chisimba-form-card"><h1>'.$e($r->text('unsubscribe')).'</h1>';
if($error)echo '<p role="alert">'.$e($r->text($error)).'</p>';
elseif($this->getVar('audienceUnsubscribed'))echo '<p>'.$e($r->text('unsubscribed_done')).'</p>';
else echo '<p>'.$e($r->text('unsubscribe_help')).'</p><form method="post" action="'.$e($this->uri(['action'=>'unsubscribe'],'audience')).'"><input type="hidden" name="token" value="'.$e($this->getVar('audienceUnsubscribeToken')).'"><input type="hidden" name="csrf_token" value="'.$e($this->getVar('audienceCsrf')).'">'.$r->button('unsubscribe','mail').'</form>';
echo '</section>';
