<?php
$i=$this->getVar('paymentIntent');
$state=(string)$i['state'];
$good=$state==='succeeded';
$pendingRegistration=(bool)$this->getVar('paymentPendingRegistration',false);
$language=$this->getObject('language','language');
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$text=static fn($key)=>$language->languageText('mod_payment_service_'.$key,'payment-service');
$isMembership=$good&&($i['purpose_type']??'')==='membership';
$browseUrl=$this->uri(array('action'=>'catalogue','access'=>(string)($i['purpose_id']??'')),'context');
?>
<section class="payment-workbench payment-outcome" aria-labelledby="payment-outcome-title">
 <header class="payment-outcome__header"><p class="eyebrow"><?=$e($text($isMembership&&!$pendingRegistration?'membership_active':'payment_status'))?></p><h1 id="payment-outcome-title"><?=$e($text($isMembership&&!$pendingRegistration?'membership_welcome':($good?'payment_confirmed':'checkout_update')))?></h1><?php if($isMembership&&!$pendingRegistration):?><p><?=$e($text('membership_welcome_intro'))?></p><?php endif;?></header>
 <div class="payment-status payment-status-<?=$e($state)?>"><strong><?=$e(ucwords(str_replace('_',' ',$state)))?></strong>
 <?php if($state==='awaiting_approval'||$state==='processing'):?><p><?=$e($text('awaiting_provider'))?></p>
 <?php elseif($good&&$pendingRegistration):?><p><?=$e($text('pending_registration_paid'))?></p>
 <?php elseif($good):?><p><?=$e($text('access_ready'))?></p>
 <?php elseif(in_array($state,array('refunded','reversed','disputed'),true)):?><p><?=$e($text('payment_attention'))?></p>
 <?php else:?><p><?=$e($text('access_not_granted'))?></p><?php endif;?></div>
 <?php if($isMembership&&!$pendingRegistration):?><section class="payment-next-step" aria-labelledby="payment-next-step-title"><h2 id="payment-next-step-title"><?=$e($text('next_step'))?></h2><p><?=$e($text('explore_intro'))?></p><div class="chisimba-form-actions"><a class="button" href="<?=$e($browseUrl)?>"><?=$e($language->code2Txt('mod_payment_service_explore_contexts','payment-service'))?></a><a class="button chisimba-button-secondary" href="<?=$e($this->uri(array('action'=>'tiers'),'payment-service'))?>"><?=$e($text('view_membership'))?></a></div></section>
 <?php else:?><div class="chisimba-form-actions"><?php if($good&&$pendingRegistration):?><a class="button" href="<?=$e($this->uri(array('action'=>'checkemail'),'registration-service'))?>"><?=$e($text('verify_account'))?></a><?php elseif($good&&$i['purpose_type']==='private_course'):?><a class="button" href="<?=$e($this->uri(array('action'=>'joincontext','contextcode'=>$i['purpose_id']),'context'))?>"><?=$e($language->code2Txt('mod_payment_service_open_context','payment-service'))?></a><?php elseif($state==='awaiting_approval'||$state==='processing'):?><a class="button" href="<?=$e($this->uri(array('action'=>'return','intent_id'=>$i['id'])))?>"><?=$e($text('refresh_status'))?></a><?php endif;?> <?php if(!$good):?><a class="button chisimba-button-secondary" href="<?=$e($this->uri(array()))?>"><?=$e($text('back_to_options'))?></a><?php endif;?></div><?php endif;?>
</section>
