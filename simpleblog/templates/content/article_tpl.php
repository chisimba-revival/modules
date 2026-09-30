<?php
$r=$this->getObject('publishingrenderer','simpleblog');$e=array('publishingrenderer','escape');$post=$this->getVar('blogPost');
$readerPost=$this->getObject('accesspreview','simpleblog')->project($post);
if($post['post_type']==='site'&&$post['post_status']==='posted'&&!$this->getVar('blogPreview'))foreach($this->getObject('pagemetadata','ui')->build($post['post_title'],$r::excerpt($readerPost),['module'=>'simpleblog','action'=>'view','id'=>$post['id']]) as $key=>$value)$this->setVar($key,$value);
$this->setVar('og_title',$post['post_title']);
$this->setVar('og_content',$r::excerpt($readerPost));
if($this->getVar('blogPreview')) {
 $this->appendArrayVar('headerParams','<meta name="robots" content="noindex,nofollow">');
 echo '<aside class="chisimba-guidance-card"><p role="status">'.$e($r->text($this->getParam('saved')==='1'?'saved':'preview')).' — '.$e($r->text($post['post_status']==='posted'?'published':'draft')).'</p></aside>';
}
echo '<nav class="chisimba-form-actions">'.$r->button('view','arrow-left',array('scope'=>$post['post_type'],'blogid'=>$post['blogid']));
if($this->getObject('publishingpolicy','simpleblog')->canEdit($post)) echo $r->button('edit','pencil',array('action'=>'edit','id'=>$post['id']));
echo '</nav><div class="chisimba-publishing-layout"><main>'.$r->article($post,(bool)$this->getVar('blogPreview'));
if($this->getObject('publishingpolicy','simpleblog')->canEdit($post)) {
 $token=$this->getObject('nativeauthwebcomposition','security')->build()['csrf']->issue('simpleblog_publish');
 echo '<details class="chisimba-form-section"><summary>'.$e($r->text('delete')).'</summary><p>'.$e($r->text('delete_help')).'</p><form method="post" action="'.$e($this->uri(array('action'=>'delete'),'simpleblog')).'"><input type="hidden" name="id" value="'.$e($post['id']).'"><input type="hidden" name="csrf_token" value="'.$e($token).'"><button class="button" type="submit">'.$this->getObject('iconservice','ui')->render('trash-2',array('decorative'=>true)).'<span>'.$e($r->text('confirm_delete')).'</span></button></form></details>';
}

echo '</main>'.$this->getObject('publishingsidebar','simpleblog')->page($post['post_type'],$post['blogid'],$post['id']).'</div>';
