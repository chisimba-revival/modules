<?php
/** Read-only confirmation; the existing POST action rechecks permission and CSRF. */
$r=$this->getObject('publishingrenderer','simpleblog');$e=array('publishingrenderer','escape');
$post=$this->getVar('blogPost');
?>
<section class="chisimba-form-page"><div class="chisimba-form-card">
<h1><?php echo $e($r->text('delete')); ?></h1>
<h2><?php echo $e($post['post_title']); ?></h2>
<p><?php echo $e($r->identity($post['post_type'],$post['blogid'])); ?></p>
<p><?php echo $e($r->text('delete_help')); ?></p>
<?php echo $this->getObject('contextualhelp','help')->show('simpleblog','publishing'); ?>
<form method="post" class="chisimba-form" action="<?php echo $e($this->uri(array('action'=>'delete'),'simpleblog')); ?>">
<input type="hidden" name="id" value="<?php echo $e($post['id']); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($this->getVar('blogToken')); ?>">
<div class="chisimba-form-actions chisimba-form-actions--equal">
<button class="button chisimba-button-secondary" type="submit"><?php echo $this->getObject('iconservice','ui')->render('trash-2',array('decorative'=>true)); ?><span><?php echo $e($r->text('confirm_delete')); ?></span></button>
<?php echo $r->button('cancel','x',array('action'=>'preview','id'=>$post['id'])); ?>
</div></form></div></section>
