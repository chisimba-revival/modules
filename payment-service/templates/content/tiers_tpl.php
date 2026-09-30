<?php
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$language=$this->getObject('language','language');
$memberships=$this->getObject('membershipservice','membership-service');$labels=[];$ranks=[];foreach($memberships->tiers(true) as $code=>$tier){$labels[$code]=$memberships->tierLabel($code);$ranks[$code]=$tier['rank'];}$baseline=$memberships->baselineTier();$currentRank=$memberships->tiers()[$tierEffective]['rank']??-1;
$money=static function($price){if(!$price)return ''; $amount=number_format(((int)$price['amount_minor'])/100,2);return strtoupper((string)$price['currency'])==='ZAR'?'R'.$amount:(string)$price['currency'].' '.$amount;};
?>
<section class="payment-workbench membership-plans" aria-labelledby="membership-plans-title">
 <header><p class="eyebrow">MEMBERSHIP</p><h1 id="membership-plans-title">Choose how you want to learn</h1><p>Compare membership levels and explore the courses available at each level. <?php if($tierIsLoggedIn):?>Your current membership is <strong><?=$e($memberships->tierLabel($tierEffective))?></strong>.<?php else:?>Register free to begin, or compare the additional learning available with membership.<?php endif;?></p></header>
 <?php if($this->getVar('paymentMessage','')):?><div class="success"><?=$e($this->getVar('paymentMessage',''))?></div><?php endif;?>
 <?php if($this->getVar('paymentError','')):?><div class="error"><?=$e($this->getVar('paymentError',''))?></div><?php endif;?>
 <div class="membership-plan-grid">
 <?php foreach($labels as $code=>$label):$content=$tierContent[$code];$products=$tierProducts[$code]??array();$isCurrent=$tierIsLoggedIn&&$tierEffective===$code;$isIncluded=$tierIsLoggedIn&&($ranks[$code]??0)<$currentRank;?>
  <article class="membership-plan<?=$isCurrent?' membership-plan--current':''?>">
   <div class="membership-plan__top"><?php if($isCurrent):?><span class="membership-plan__current">Your current tier</span><?php elseif($isIncluded):?><span class="membership-plan__included">Included</span><?php endif;?><h2><?=$e($label)?></h2>
   <div class="membership-plan__price"><?php if($code===$baseline):?>No membership fee<?php elseif(!$products):?>Contact us for pricing<?php endif;?></div><p><?=$e($content['summary'])?></p></div>
   <ul class="membership-plan__features"><?php foreach(preg_split('/\R/u',$content['features']) as $feature):?><li><?=$e($feature)?></li><?php endforeach;?></ul>
   <div class="membership-plan__actions"><a class="button chisimba-button-secondary" href="<?=$this->uri(array('action'=>'catalogue','access'=>$code),'context')?>">View <?=$e($code===$baseline?'free courses':$label.' courses')?></a>
   <?php if(!$tierIsLoggedIn):?>
    <?php if($code===$baseline):?>
     <?php $afterRegistration=html_entity_decode($this->uri(array('action'=>'catalogue','access'=>$baseline),'context'),ENT_QUOTES,'UTF-8');$afterParts=parse_url($afterRegistration);$afterRegistration=(string)($afterParts['path']??'/index.php').(isset($afterParts['query'])?'?'.$afterParts['query']:'');?><a class="button" href="<?=$this->uri(array('return_to'=>$afterRegistration),'registration-service')?>"><?=$e($language->languageText('mod_payment_service_register_free','payment-service'))?></a>
    <?php else:?>
     <form class="membership-billing-choice" method="get" action="<?=$this->uri(array(),'registration-service')?>"><input type="hidden" name="module" value="registration-service"><fieldset><legend><?=$e($language->languageText('mod_payment_service_choose_billing','payment-service'))?></legend><?php foreach($products as $index=>$product):$period=(string)($product['billing_period']??'monthly');$price=$product['current_price'];$afterRegistration=html_entity_decode($this->uri(array('action'=>'catalogue','product'=>(string)$product['code']),'payment-service'),ENT_QUOTES,'UTF-8');$afterParts=parse_url($afterRegistration);$afterRegistration=(string)($afterParts['path']??'/index.php').(isset($afterParts['query'])?'?'.$afterParts['query']:'');?><label><input type="radio" name="return_to" value="<?=$e($afterRegistration)?>"<?=$index===0?' checked':''?>><span><strong><?=$e($language->languageText('mod_payment_service_choose_'.$period,'payment-service'))?></strong><small><?=$e($money($price))?> <?=$e($language->languageText('mod_payment_service_price_'.$period,'payment-service'))?></small><?php if($period==='annual'):?><small><?=$e($money(array('amount_minor'=>(int)round(((int)$price['amount_minor'])/12),'currency'=>$price['currency'])))?> <?=$e($language->languageText('mod_payment_service_annual_price_note','payment-service'))?></small><?php endif;?></span></label><?php endforeach;?></fieldset><button class="button" type="submit"><?=$e($language->languageText('mod_payment_service_continue','payment-service'))?></button></form>
    <?php endif;?>
   <?php elseif(!$isCurrent&&!$isIncluded&&$code!==$baseline&&$products):?><a class="button" href="<?=$this->uri(array('action'=>'catalogue','purpose'=>'membership','tier'=>$code),'payment-service')?>">Upgrade to <?=$e($label)?></a><?php endif;?></div>
  </article>
 <?php endforeach;?></div>
 <?php if($paymentIsAdmin):?><details class="membership-plan-editor"<?=$tierEditOpen?' open':''?>><summary>Edit membership page</summary><form method="post" action="<?=$this->uri(array('action'=>'savetiers'))?>"><input type="hidden" name="csrf_token" value="<?=$e($paymentCsrf)?>">
 <?php foreach($labels as $code=>$label):?><fieldset><legend><?=$e($label)?></legend><label>Summary<textarea name="<?=$e($code)?>_summary" rows="3" required><?=$e($tierContent[$code]['summary'])?></textarea></label><label>Features <span class="caption">One per line</span><textarea name="<?=$e($code)?>_features" rows="5" required><?=$e($tierContent[$code]['features'])?></textarea></label></fieldset><?php endforeach;?>
 <button class="button" type="submit">Save membership page</button></form></details><?php endif;?>
</section>
