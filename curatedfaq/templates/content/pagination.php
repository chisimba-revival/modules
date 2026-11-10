<?php if(($faqPage??1)>1||!empty($faqMore)): ?><nav class="chisimba-form-actions" aria-label="<?= $e($t('pages')) ?>">
<?php $params=['action'=>!empty($managing)?'manage':'view','q'=>is_string($this->getParam('q'))?$this->getParam('q'):'','category'=>is_string($this->getParam('category'))?$this->getParam('category'):''];
if($faqPage>1)echo $button('previous','chevron-left',$params+['page'=>$faqPage-1]);
if(!empty($faqMore))echo $button('next','chevron-right',$params+['page'=>$faqPage+1]); ?>
</nav><?php endif; ?>
