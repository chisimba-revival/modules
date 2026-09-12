<?php
$r=$this->getObject('publishingrenderer','simpleblog');$e=array('publishingrenderer','escape');$post=$this->getVar('blogPost');
$this->setVar('og_title',$post['post_title']);
$this->setVar('og_content',mb_substr(strip_tags($post['post_content']),0,240));
if($this->getVar('blogPreview')) {
 $this->appendArrayVar('headerParams','<meta name="robots" content="noindex,nofollow">');
 echo '<aside class="chisimba-guidance-card"><p role="status">'.$e($r->text($this->getParam('saved')==='1'?'saved':'preview')).' — '.$e($r->text($post['post_status']==='posted'?'published':'draft')).'</p></aside>';
}
echo '<nav class="chisimba-form-actions">'.$r->button('view','arrow-left',array('scope'=>$post['post_type'],'blogid'=>$post['blogid']));
if($this->getObject('publishingpolicy','simpleblog')->canEdit($post)) echo $r->button('edit','pencil',array('action'=>'edit','id'=>$post['id']));
echo '</nav><div class="chisimba-publishing-layout"><main>'.$r->article($post);
if($this->getObject('publishingpolicy','simpleblog')->canEdit($post)) {
 $token=$this->getObject('nativeauthwebcomposition','security')->build()['csrf']->issue('simpleblog_publish');
 echo '<details class="chisimba-form-section"><summary>'.$e($r->text('delete')).'</summary><p>'.$e($r->text('delete_help')).'</p><form method="post" action="'.$e($this->uri(array('action'=>'delete'),'simpleblog')).'"><input type="hidden" name="id" value="'.$e($post['id']).'"><input type="hidden" name="csrf_token" value="'.$e($token).'"><button class="button" type="submit">'.$this->getObject('iconservice','ui')->render('trash-2',array('decorative'=>true)).'<span>'.$e($r->text('confirm_delete')).'</span></button></form></details>';
}

echo '</main>'.$this->getObject('publishingsidebar','simpleblog')->page($post['post_type'],$post['blogid'],$post['id']).'</div>';
