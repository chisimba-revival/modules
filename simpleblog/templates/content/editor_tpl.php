<?php
$r=$this->getObject('publishingrenderer','simpleblog');$e=array('publishingrenderer','escape');
$post=$this->getVar('blogPost');$type=$this->getVar('blogType');$scope=$this->getVar('blogScope');
$input=$this->getVar('blogInput') ?: array('title'=>$post['post_title']??'','content'=>$post['post_content']??'','tags'=>$post['post_tags']??'','status'=>$post['post_status']??'draft');
?>
<section class="chisimba-form-page"><div class="chisimba-form-card chisimba-form-card--wide"><h1><?php echo $e($r->text($post?'edit':'new')); ?></h1>
<aside class="chisimba-guidance-card"><p><?php echo $e($r->identity($type,$scope)); ?></p><p><?php echo $e($r->text('author')).': '.$e($this->getObject('user','security')->fullname($post['userid']??$this->getObject('user','security')->userId())); ?></p></aside>
<?php echo $this->getObject('contextualhelp','help')->show('simpleblog','publishing'); ?>
<?php if($this->getVar('blogError')): ?><p role="alert"><?php echo $e($r->text($this->getVar('blogError'))); ?></p><?php endif; ?>
<form method="post" class="chisimba-form" action="<?php echo $e($this->uri(array('action'=>'save'),'simpleblog')); ?>">
<input type="hidden" name="version" value="<?php echo $e($this->getVar('blogInput')['version']??($post?publishingservice::version($post):'')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $e($this->getVar('blogToken')); ?>"><input type="hidden" name="id" value="<?php echo $e($post['id']??''); ?>"><input type="hidden" name="scope" value="<?php echo $e($type); ?>"><input type="hidden" name="blogid" value="<?php echo $e($scope); ?>">
<div class="chisimba-form-field"><label for="blog-title"><?php echo $e($r->text('title')); ?></label><input id="blog-title" type="text" name="title" required maxlength="250" value="<?php echo $e($input['title']); ?>"></div>
<div class="chisimba-form-field"><label for="input_content"><?php echo $e($r->text('content')); ?></label><?php $editor=$this->newObject('htmlarea','htmlelements');$editor->name='content';$editor->width='100%';$editor->cssId='input_content';$editor->label=$r->text('content');$editor->setContent($input['content']);echo $editor->show(); ?></div>
<div class="chisimba-form-field"><label for="blog-tags"><?php echo $e($r->text('tags')); ?></label><input id="blog-tags" type="text" name="tags" maxlength="2000" value="<?php echo $e($input['tags']); ?>"></div>
<div class="chisimba-form-actions"><button name="status" value="draft" class="button chisimba-button-secondary"><?php echo $this->getObject('iconservice','ui')->render('save',array('decorative'=>true)); ?><span><?php echo $e($r->text('save_draft')); ?></span></button><button name="status" value="posted" class="button chisimba-button-primary"><?php echo $this->getObject('iconservice','ui')->render('send',array('decorative'=>true)); ?><span><?php echo $e($r->text('publish')); ?></span></button><?php echo $r->button('manage','list',array('action'=>'manage','scope'=>$type,'blogid'=>$scope)); ?></div>
<p class="chisimba-field-help"><?php echo $e($r->text('save_help')); ?></p></form></div></section>
