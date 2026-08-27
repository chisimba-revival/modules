<?php
$products=$this->getVar('paymentProducts',array());
$csrf=htmlspecialchars($this->getVar('paymentCsrf',''),ENT_QUOTES,'UTF-8');
$learner=htmlspecialchars($this->getVar('paymentLearnerName',''),ENT_QUOTES,'UTF-8');
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$provider=$e($this->getVar('paymentProviderName','payment provider'));
$requested=(string)$this->getVar('paymentRequestedProduct','');
$purpose=(string)$this->getVar('paymentCataloguePurpose','');
$effectiveTier=(string)$this->getVar('paymentEffectiveTier','free');
$tierLabel=static function($tier){return match((string)$tier){'tier_1'=>'Tier 1','tier_2'=>'Tier 2',default=>'Free',};};
$membershipBrowse=$purpose==='membership'&&$requested==='';
$money=static function($price){$amount=number_format(((int)$price['amount_minor'])/100,2);return strtoupper((string)$price['currency'])==='ZAR'?'R'.$amount:(string)$price['currency'].' '.$amount;};
$period=static function($value){return match((string)$value){'one_off'=>'Once-off payment','monthly'=>'Monthly membership','annual'=>'Annual membership',default=>ucwords(str_replace('_',' ',(string)$value)),};};
?>
<section class="payment-workbench payment-checkout-review">
  <?php if($membershipBrowse):?>
    <header><p class="eyebrow">YOUR MEMBERSHIP</p><h1>Choose your membership</h1><p>Your current membership is <strong><?=$e($tierLabel($effectiveTier))?></strong>. Choose an available upgrade and review it before payment.</p></header>
  <?php else:?>
    <header><p class="eyebrow">SECURE COURSE ACCESS</p><h1>Review your purchase</h1><p>Confirm the details below, then continue to <?=$provider?> to pay securely.</p></header>
  <?php endif;?>
  <?php if($this->getVar('paymentError','')):?><div class="error"><?=$e($this->getVar('paymentError',''))?></div><?php endif;?>
  <div class="payment-review-grid">
  <?php foreach($products as $product): $price=$product['current_price']??null; if(!$price)continue; $course=$product['course']??null; ?>
    <?php if($membershipBrowse):?>
      <article class="payment-card payment-membership-option">
        <span class="payment-membership-tier"><?=$e($tierLabel($product['purpose_id']))?></span>
        <h2><?=$e($product['name'])?></h2>
        <p class="payment-price"><?=$e($money($price))?> <small>per <?=($product['billing_period']==='annual'?'year':'month')?></small></p>
        <p><?=($product['purpose_id']==='tier_2'?'Includes Tier 1 and Tier 2 learning.':'Includes Free and Tier 1 learning.')?></p>
        <a class="button payment-continue" href="<?=$this->uri(array('action'=>'catalogue','product'=>$product['code']))?>">Review <?=$e($tierLabel($product['purpose_id']))?></a>
      </article>
      <?php continue;?>
    <?php endif;?>
    <div class="payment-review-product<?=is_array($course)?' payment-review-product--course':' payment-review-product--membership'?>">
    <?php if(is_array($course)):?>
      <article class="course-card payment-review-course">
        <div class="course-card__media">
          <?php if(!empty($course['image'])):?><img class="course-card__image" src="<?=$e($course['image'])?>" alt="" loading="lazy"><?php else:?><div class="course-card__placeholder" aria-hidden="true"><span></span></div><?php endif;?>
          <div class="course-card__badges"><span class="course-card__badge course-card__badge--access">Private course</span></div>
        </div>
        <div class="course-card__body"><h2 class="course-card__title"><?=$e($course['title'])?></h2>
          <?php if($course['about']!==''):?><p class="course-card__summary"><?=$e($course['about'])?></p><?php endif;?>
          <?php if($course['lecturers']):?><p class="course-card__lecturer"><span>Led by</span> <?=$e(implode(', ',$course['lecturers']))?></p><?php endif;?>
        </div>
      </article>
    <?php endif;?>
    <article class="payment-card payment-order-summary" aria-labelledby="payment-order-title">
      <p class="eyebrow">YOUR ORDER</p><h2 id="payment-order-title"><?=$e($product['name'])?></h2>
      <dl><dt>Learner</dt><dd><?=$learner?></dd><dt>Access</dt><dd><?=$e($period($product['billing_period']))?></dd><dt>Total</dt><dd class="payment-price"><?=$e($money($price))?></dd></dl>
      <form method="post" action="<?=$this->uri(array('action'=>'buy'))?>"><input type="hidden" name="csrf_token" value="<?=$csrf?>"><input type="hidden" name="product_code" value="<?=$e($product['code'])?>"><button class="button payment-continue" type="submit">Continue securely with <?=$provider?></button></form>
      <p class="payment-trust"><strong>Secure checkout</strong><span><?=$provider?> handles your payment securely. This learning site never receives or stores your card details.</span></p>
    </article>
    </div>
  <?php endforeach;?>
  </div>
  <?php if(!$products):?><div class="payment-empty-state"><strong><?=($membershipBrowse?'Your '.$e($tierLabel($effectiveTier)).' membership is active.':'No purchase is needed.')?></strong><p><?=($membershipBrowse&&$effectiveTier==='tier_2'?'You already have the highest available membership tier.':'This item is no longer available or access has already been granted.')?></p><a class="button" href="<?=$this->uri(array(),'mylearning')?>">Go to My Learning</a></div><?php endif;?>
</section>
