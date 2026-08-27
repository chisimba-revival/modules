<?php
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$labels=array('free'=>'Free','tier_1'=>'Tier 1','tier_2'=>'Tier 2');$ranks=array('free'=>0,'tier_1'=>1,'tier_2'=>2);$currentRank=$ranks[$tierEffective]??0;
$money=static function($price){if(!$price)return ''; $amount=number_format(((int)$price['amount_minor'])/100,2);return strtoupper((string)$price['currency'])==='ZAR'?'R'.$amount:(string)$price['currency'].' '.$amount;};
?>
<section class="payment-workbench membership-plans" aria-labelledby="membership-plans-title">
 <header><p class="eyebrow">MEMBERSHIP</p><h1 id="membership-plans-title">Choose how you want to learn</h1><p>Compare membership levels and explore the courses available at each level. <?php if($tierIsLoggedIn):?>Your current membership is <strong><?=$e($labels[$tierEffective]??'Free')?></strong>.<?php else:?>Register free to begin, or compare the additional learning available with membership.<?php endif;?></p></header>
 <?php if($this->getVar('paymentMessage','')):?><div class="success"><?=$e($this->getVar('paymentMessage',''))?></div><?php endif;?>
 <?php if($this->getVar('paymentError','')):?><div class="error"><?=$e($this->getVar('paymentError',''))?></div><?php endif;?>
 <div class="membership-plan-grid">
 <?php foreach($labels as $code=>$label):$content=$tierContent[$code];$products=$tierProducts[$code]??array();$isCurrent=$tierIsLoggedIn&&$tierEffective===$code;$isIncluded=$tierIsLoggedIn&&($ranks[$code]??0)<$currentRank;?>
  <article class="membership-plan<?=$isCurrent?' membership-plan--current':''?>">
   <div class="membership-plan__top"><?php if($isCurrent):?><span class="membership-plan__current">Your current tier</span><?php elseif($isIncluded):?><span class="membership-plan__included">Included</span><?php endif;?><h2><?=$e($label)?></h2>
   <div class="membership-plan__price"><?php if($code==='free'):?>No membership fee<?php elseif(!$products):?>Contact us for pricing<?php else:?><?php foreach($products as $product):$price=$product['current_price'];$annual=($product['billing_period']??'monthly')==='annual';?><span><?=$e($money($price))?> <small>per <?=$annual?'year':'month'?></small><?php if($annual):?><small class="membership-plan__price-note"><?=$e($money(array('amount_minor'=>(int)round(((int)$price['amount_minor'])/12),'currency'=>$price['currency'])))?> per month, billed annually</small><?php endif;?></span><?php endforeach;?><?php endif;?></div><p><?=$e($content['summary'])?></p></div>
   <ul class="membership-plan__features"><?php foreach(preg_split('/\R/u',$content['features']) as $feature):?><li><?=$e($feature)?></li><?php endforeach;?></ul>
   <div class="membership-plan__actions"><a class="button chisimba-button-secondary" href="<?=$this->uri(array('action'=>'catalogue','access'=>$code),'context')?>">View <?=$e($code==='free'?'free courses':$label.' courses')?></a>
   <?php if(!$tierIsLoggedIn):?><?php if($code==='free'||$products):?><?php $afterRegistration=$code==='free'?$this->uri(array('action'=>'catalogue','access'=>'free'),'context'):$this->uri(array('action'=>'catalogue','purpose'=>'membership','tier'=>$code),'payment-service');?><a class="button" href="<?=$this->uri(array('return_to'=>html_entity_decode($afterRegistration,ENT_QUOTES,'UTF-8')),'registration-service')?>"><?=$code==='free'?'Register now for free courses':'Register to join '.$e($label)?></a><?php endif;?>
   <?php elseif(!$isCurrent&&!$isIncluded&&$code!=='free'&&$products):?><a class="button" href="<?=$this->uri(array('action'=>'catalogue','purpose'=>'membership','tier'=>$code),'payment-service')?>">Upgrade to <?=$e($label)?></a><?php endif;?></div>
  </article>
 <?php endforeach;?></div>
 <?php if($paymentIsAdmin):?><details class="membership-plan-editor"<?=$tierEditOpen?' open':''?>><summary>Edit membership page</summary><form method="post" action="<?=$this->uri(array('action'=>'savetiers'))?>"><input type="hidden" name="csrf_token" value="<?=$e($paymentCsrf)?>">
 <?php foreach($labels as $code=>$label):?><fieldset><legend><?=$e($label)?></legend><label>Summary<textarea name="<?=$e($code)?>_summary" rows="3" required><?=$e($tierContent[$code]['summary'])?></textarea></label><label>Features <span class="caption">One per line</span><textarea name="<?=$e($code)?>_features" rows="5" required><?=$e($tierContent[$code]['features'])?></textarea></label></fieldset><?php endforeach;?>
 <button class="button" type="submit">Save membership page</button></form></details><?php endif;?>
</section>
