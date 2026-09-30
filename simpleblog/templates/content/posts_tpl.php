<?php
$r=$this->getObject('publishingrenderer','simpleblog');$e=array('publishingrenderer','escape');
$type=$this->getVar('blogType');$scope=$this->getVar('blogScope');$manage=$this->getVar('blogManage');
$category=is_string($this->getParam('category'))?$this->getParam('category'):'';
if(!$manage&&$type==='site'){ $destination=['module'=>'simpleblog','action'=>'view'];if($category!=='')$destination['category']=$category;if((int)$this->getParam('page',1)>1)$destination['page']=(int)$this->getParam('page');foreach($this->getObject('pagemetadata','ui')->build($r->listingTitle($type,$scope,$category),$r->listingTitle($type,$scope,$category),$destination) as $key=>$value)$this->setVar($key,$value); }
$p=$this->getObject('publishingpolicy','simpleblog');$u=$this->getObject('user','security');
?>
<section class="chisimba-publishing-layout"><div class="chisimba-form-card chisimba-form-card--wide chisimba-publication-list">
<header class="chisimba-publication-list-header"><div><h1><?php echo $e($manage?$r->text('manage'):$r->listingTitle($type,$scope,$category)); ?></h1><?php if($manage)echo '<p>'.$e($r->identity($type,$scope)).'</p>'; ?></div>
<?php if($p->canCreate($type,$scope)||$p->personalPublisher()||$type!=='site'): ?><nav class="chisimba-form-actions chisimba-publication-list-actions">
<?php
if($p->canCreate($type,$scope)) echo $r->button('new','plus',array('action'=>'edit','scope'=>$type,'blogid'=>$scope),true).$r->button($manage?'view':'manage','list',array('action'=>$manage?'view':'manage','scope'=>$type,'blogid'=>$scope));
if($p->personalPublisher() && !($manage && $type==='personal' && $scope===$u->userId())) echo $r->button('personal','user',array('action'=>'manage','scope'=>'personal','blogid'=>$u->userId()));
if($type!=='site') echo $r->button('site','newspaper',array('scope'=>'site','blogid'=>'site'));
?>
</nav><?php endif; ?>
<form method="get" class="chisimba-form chisimba-publication-search"><input type="hidden" name="module" value="simpleblog"><input type="hidden" name="action" value="<?php echo $manage?'manage':'view'; ?>"><input type="hidden" name="scope" value="<?php echo $e($type); ?>"><input type="hidden" name="blogid" value="<?php echo $e($scope); ?>">
<?php if($category!==''): ?><input type="hidden" name="category" value="<?php echo $e($category); ?>"><?php endif; ?>
<div class="chisimba-form-field"><input id="blog-search" aria-label="<?php echo $e($r->text('search')); ?>" placeholder="<?php echo $e($r->text('search')); ?>" name="search" type="search" value="<?php echo $e(is_string($this->getParam('search'))?$this->getParam('search'):''); ?>"></div>
<?php if($manage): ?><div class="chisimba-form-field"><label for="blog-status"><?php echo $e($r->text('status')); ?></label><select id="blog-status" name="status"><?php foreach(array('all'=>'all','draft'=>'draft','posted'=>'published') as $value=>$label): ?><option value="<?php echo $value; ?>" <?php if($this->getVar('blogStatus')===$value)echo 'selected'; ?>><?php echo $e($r->text($label)); ?></option><?php endforeach; ?></select></div><?php endif; ?>
<button type="submit" class="button" aria-label="<?php echo $e($r->text('search')); ?>" title="<?php echo $e($r->text('search')); ?>"><?php echo $this->getObject('iconservice','ui')->render('search',array('decorative'=>true)); ?></button></form><?php echo $this->getObject('contextualhelp','help')->show('simpleblog','publishing',true); ?></header>


<?php echo $r->posts($type,$scope,$manage,max(1,(int)$this->getParam('page',1)),$this->getVar('blogStatus'),is_string($this->getParam('search'))?$this->getParam('search'):'',is_string($this->getParam('tag'))?$this->getParam('tag'):'',(int)$this->getParam('year',0),(int)$this->getParam('month',0),false,is_string($this->getParam('category'))?$this->getParam('category'):''); ?>
</div><?php echo $this->getObject('publishingsidebar','simpleblog')->page($type,$scope); ?></section>
