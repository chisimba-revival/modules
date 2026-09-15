<?php
/** Accessible guest order review and verified payment outcome. @author Derek Keats */
$e=static fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
$lang=$this->getObject('language','language');$t=static fn($key)=>$lang->code2Txt('mod_payment_service_contribution_'.$key,'payment-service');
$service=$this->getObject('contributionservice');$money=static fn($n)=>contributionservice::money($n);
$order=$this->getVar('contributionOrder');$intent=$this->getVar('contributionIntent');$returned=$this->getVar('contributionReturned');$error=$this->getVar('contributionError','');
?>
<section class="payment-workbench" aria-labelledby="contribution-title">
<header><h1 id="contribution-title"><?=$e($t('title'))?></h1><p><?=$e($t('intro'))?></p></header>
<?php if($error):?><p role="alert" class="chisimba-alert chisimba-alert--error"><?=$e($t('error_'.$error))?></p><?php endif;?>
<?php if($returned&&$order): $paid=($intent['state']??'')==='succeeded';$state=$intent['state']??'created'; ?>
<section class="payment-next-step" aria-live="polite">
<h2><?=$e($t($paid?'thanks':'status'))?></h2>
<p><?=$e($t('state_'.(in_array($state,array('succeeded','failed','refunded','reversed','disputed'),true)?$state:'pending')))?></p>
<?php if(!$paid&&$this->getVar('contributionOutcome')):?><p><?=$e($t('return_'.$this->getVar('contributionOutcome')))?></p><?php endif;?>
<h3><?=$e($order['product_name'])?></h3>
<dl><dt><?=$e($t('net'))?></dt><dd><?=$e($money($order['amount_minor']-$order['vat_minor']))?></dd><dt><?=$e($t('vat'))?></dt><dd><?=$e($money($order['vat_minor']))?></dd><dt><?=$e($t('total'))?></dt><dd><strong><?=$e($money($order['amount_minor']))?></strong></dd><dt><?=$e($t('reference'))?></dt><dd><?=$e($order['id'])?></dd></dl>
<div class="chisimba-form-actions"><a class="button chisimba-button-secondary" href="<?=$e($this->uri(array('action'=>'contributionreturn','order'=>$order['id']),'payment-service'))?>"><?=$this->getObject('iconservice','ui')->render('refresh-cw',array('decorative'=>true))?> <?=$e($t('refresh'))?></a><a class="button chisimba-button-secondary" href="<?=$e($this->uri(array('action'=>'contributions'),'payment-service'))?>"><?=$e($t('back'))?></a></div>
</section>
<?php elseif(!$returned):?>
<?php if(!$this->getVar('contributionReady')):?><p role="status"><?=$e($t('error_unavailable'))?></p><?php endif;?>
<form method="post" action="<?=$e($this->uri(array('action'=>'contributebuy'),'payment-service'))?>" class="chisimba-form">
<input type="hidden" name="csrf_token" value="<?=$e($this->getVar('paymentCsrf'))?>">
<fieldset><legend><?=$e($t('choose'))?></legend><div class="chisimba-choice-cards">
<?php foreach($this->getVar('contributionProducts',array()) as $i=>$p):$price=$p['current_price'];$vat=(int)($price['vat_minor']??0);?>
<label class="chisimba-card"><span><input type="radio" name="product" value="<?=$e($p['code'])?>" required <?=($this->getVar('contributionSelected','')===$p['code']||($this->getVar('contributionSelected','')===''&&$i===0))?'checked':''?>> <strong><?=$e($p['name'])?></strong></span><span class="chisimba-choice-card-price"><?=$e($money($price['amount_minor']))?></span><span><?=$e($t('net'))?>: <?=$e($money($price['amount_minor']-$vat))?></span><span><?=$e($t('vat'))?>: <?=$e($money($vat))?></span><small><?=$e($t('once'))?></small></label>
<?php endforeach;?></div></fieldset>
<label for="contribution-name"><?=$e($t('name'))?></label><input id="contribution-name" name="name" autocomplete="name" maxlength="191" required value="<?=$e($this->getVar('contributionName',''))?>">
<label for="contribution-email"><?=$e($t('email'))?></label><input id="contribution-email" name="email" type="email" autocomplete="email" maxlength="254" required value="<?=$e($this->getVar('contributionEmail',''))?>">
<p><?=$e($t('privacy'))?></p><div class="chisimba-form-actions"><button type="submit" class="button" <?=$this->getVar('contributionReady')?'':'disabled'?>><?=$this->getObject('iconservice','ui')->render('credit-card',array('decorative'=>true))?> <?=$e($t('pay'))?></button></div>
</form>
<?php endif;?>
<?=$this->getObject('contextualhelp','help')->show('payment-service','contributions',true)?>
</section>
