<?php
$r=$this->getObject('publishingrenderer','simpleblog');$e=array('publishingrenderer','escape');
$post=$this->getVar('blogPost');$type=$this->getVar('blogType');$scope=$this->getVar('blogScope');
$input=$this->getVar('blogInput') ?: array('featured_image'=>$post['featured_image']??'','featured_alt'=>$post['featured_alt']??'','title'=>$post['post_title']??'','content'=>$post['post_content']??'','tags'=>$post['post_tags']??'','status'=>$post['post_status']??'draft','blocks'=>$this->getObject('compositionservice','contentblocks')->fromPost($post?:[]));
?>
<section class="chisimba-form-page"><div class="chisimba-form-card chisimba-form-card--wide"><h1><?php echo $e($r->text($post?'edit':'new')); ?></h1>
<aside class="chisimba-guidance-card"><p><?php echo $e($r->identity($type,$scope)); ?></p><p><?php echo $e($r->text('author')).': '.$e($this->getObject('user','security')->fullname($post['userid']??$this->getObject('user','security')->userId())); ?></p></aside>
<?php echo $this->getObject('contextualhelp','help')->show('simpleblog','publishing'); ?>
<?php if($this->getVar('blogInput'))echo '<p role="status">'.$e($r->text('unsaved_composition')).'</p>'; ?>
<?php if($this->getVar('blogError')): ?><p role="alert"><?php echo $e($r->text($this->getVar('blogError'))); ?></p><?php endif; ?>
<form method="post" class="chisimba-form chisimba-composition-form" action="<?php echo $e($this->uri(array('action'=>'save'),'simpleblog')); ?>">
<input type="hidden" name="version" value="<?php echo $e($this->getVar('blogInput')['version']??($post?publishingservice::version($post):'')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $e($this->getVar('blogToken')); ?>"><input type="hidden" name="id" value="<?php echo $e($post['id']??''); ?>"><input type="hidden" name="scope" value="<?php echo $e($type); ?>"><input type="hidden" name="blogid" value="<?php echo $e($scope); ?>">
<div class="chisimba-form-field"><label for="blog-title"><?php echo $e($r->text('title')); ?></label><input id="blog-title" type="text" name="title" required maxlength="250" value="<?php echo $e($input['title']); ?>"></div>
<input type="hidden" name="content" value=""><input type="hidden" name="composition_undo" value="<?php echo $e($input['undo']??''); ?>">
<?php if(!empty($input['undo']))echo '<button class="button" type="submit" name="compose_command" value="undo_blocks" formnovalidate>'.$this->getObject('iconservice','ui')->render('undo-2',['decorative'=>true]).'<span>'.$e($r->text('undo_blocks')).'</span></button>'; ?><div class="chisimba-publishing-layout"><main>
<?php echo $this->getObject('compositioneditor','contentblocks')->show(is_array($input['blocks']??null)?$input['blocks']:[],false,$input['active']??''); ?>
</main><aside class="chisimba-publishing-sidebar">
<?php
echo $this->getObject('compositioneditor','contentblocks')->palette();
echo '<fieldset class="chisimba-card"><legend>'.$e($r->text('featured_image')).'</legend>';
echo '<p>'.$e($r->text('featured_help')).'</p><div class="chisimba-form-field"><label for="comp_featured_image">'.$e($r->text('featured_url')).'</label><input id="comp_featured_image" name="featured_image" type="text" value="'.$e($input['featured_image']??'').'"></div>';
echo '<button type="button" class="button chisimba-button-secondary" data-composition-media="comp_featured_image">'.$this->getObject('iconservice','ui')->render('image',['decorative'=>true]).'<span>'.$e($r->text('choose_featured')).'</span></button>';
echo '<div class="chisimba-form-field"><label for="featured-alt">'.$e($r->text('featured_alt')).'</label><input id="featured-alt" name="featured_alt" type="text" value="'.$e($input['featured_alt']??'').'"></div></fieldset>';

$classification=$this->getObject('publishingservice','simpleblog')->classification();
$categories=$classification->creationChoices('simpleblog',$type,$scope,'category');
$selected=$input['categories']??($post?array_column($classification->forItem('simpleblog',$post['id'],'category',true),'id'):[]);
echo '<fieldset><legend>'.$e($r->text('categories')).'</legend>';
foreach($categories as $category) echo '<label><input type="checkbox" name="categories[]" value="'.$e($category['id']).'" '.(in_array($category['id'],is_array($selected)?$selected:[],true)?'checked':'').'> '.$e($category['name']).'</label>';
if(!$categories)echo '<p>'.$e($r->text('no_categories')).'</p>';
echo '</fieldset>';
if($classification->mayManage($type,$scope,'category')) {
 echo '<details><summary>'.$e($r->text('add_category')).'</summary><div class="chisimba-form-field"><label for="category-name">'.$e($r->text('category_name')).'</label><input type="text" id="category-name" name="category_name"></div><div class="chisimba-form-field"><label for="category-parent">'.$e($r->text('category_parent')).'</label><select id="category-parent" name="category_parent"><option value="">'.$e($r->text('category_root')).'</option>';
 foreach($categories as $category)echo '<option value="'.$e($category['id']).'">'.$e($category['name']).'</option>';
 echo '</select></div><button type="submit" formnovalidate name="compose_command" value="category_add" class="button">'.$this->getObject('iconservice','ui')->render('plus',['decorative'=>true]).'<span>'.$e($r->text('add_category')).'</span></button></details>';
}

?>
<div class="chisimba-form-field"><label for="blog-tags"><?php echo $e($r->text('tags')); ?></label><input id="blog-tags" list="blog-tag-suggestions" autocomplete="off" type="text" name="tags" maxlength="2000" value="<?php echo $e($input['tags']); ?>"></div>
<datalist id="blog-tag-suggestions"></datalist><div class="chisimba-form-actions"><button name="status" value="draft" class="button chisimba-button-secondary"><?php echo $this->getObject('iconservice','ui')->render('save',array('decorative'=>true)); ?><span><?php echo $e($r->text('save_draft')); ?></span></button><button name="status" value="posted" class="button chisimba-button-primary"><?php echo $this->getObject('iconservice','ui')->render('send',array('decorative'=>true)); ?><span><?php echo $e($r->text('publish')); ?></span></button><?php echo $r->button('manage','list',array('action'=>'manage','scope'=>$type,'blogid'=>$scope)); ?></div>
<p class="chisimba-field-help"><?php echo $e($r->text('save_help')); ?></p><input type="hidden" name="composition_complete" value="1"></aside></div></form></div></section>

<?php
$suggest=$this->uri(['action'=>'tag_suggestions','scope'=>$type,'blogid'=>$scope],'simpleblog');
$this->appendArrayVar('headerParams','<script>window.ChisimbaBlogTagSuggestions='.json_encode(html_entity_decode($suggest,ENT_QUOTES,'UTF-8'),JSON_HEX_TAG|JSON_HEX_AMP).';</script><script defer src="'.$e($this->getResourceUri('publishing.js','simpleblog')).'"></script>');
?>
