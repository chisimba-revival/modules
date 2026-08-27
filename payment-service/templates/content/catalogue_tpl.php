<?php
$products=$this->getVar('paymentProducts',array());
$csrf=htmlspecialchars($this->getVar('paymentCsrf',''),ENT_QUOTES,'UTF-8');
$learner=htmlspecialchars($this->getVar('paymentLearnerName',''),ENT_QUOTES,'UTF-8');
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$provider=$e($this->getVar('paymentProviderName','payment provider'));
$money=static function($price){$amount=number_format(((int)$price['amount_minor'])/100,2);return strtoupper((string)$price['currency'])==='ZAR'?'R'.$amount:(string)$price['currency'].' '.$amount;};
$period=static function($value){return match((string)$value){'one_off'=>'Once-off payment','monthly'=>'Monthly membership','annual'=>'Annual membership',default=>ucwords(str_replace('_',' ',(string)$value)),};};
?>
<section class="payment-workbench payment-checkout-review">
  <header><p class="eyebrow">SECURE COURSE ACCESS</p><h1>Review your purchase</h1><p>Confirm the details below, then continue to <?=$provider?> to pay securely.</p></header>
  <?php if($this->getVar('paymentError','')):?><div class="error"><?=$e($this->getVar('paymentError',''))?></div><?php endif;?>
  <div class="payment-review-grid">
  <?php foreach($products as $product): $price=$product['current_price']??null; if(!$price)continue; $course=$product['course']??null; ?>
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
  <?php endforeach;?>
  </div>
  <?php if(!$products):?><div class="payment-empty-state"><strong>No purchase is needed.</strong><p>This item is no longer available or access has already been granted.</p></div><?php endif;?>
</section>
