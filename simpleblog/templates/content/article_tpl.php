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
if($this->getObject('publishingpolicy','simpleblog')->canEdit($post)) echo $r->button('edit','pencil',array('action'=>'edit','id'=>$post['id'])).$r->button('delete','trash-2',array('action'=>'confirmdelete','id'=>$post['id']));
echo '</nav><div class="chisimba-publishing-layout"><main>'.$r->article($post,(bool)$this->getVar('blogPreview'));

echo '</main>'.$this->getObject('publishingsidebar','simpleblog')->page($post['post_type'],$post['blogid'],$post['id']).'</div>';
