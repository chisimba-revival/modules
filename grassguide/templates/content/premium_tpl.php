<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
$premium=$this->getObject('grassguidepremium','grassguide');
$status=$premium->entitlement($s->actor());
echo '<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h2>'.$t('premium_access').'</h2>';
if(!$status['premium']){echo '<p>'.$t('premium_required').'</p></section>';return;}
echo '<p>'.$t($status['lifetime']?'premium_lifetime':'premium_active').'</p><p>'.$t('premium_intro').'</p><p>'.$t('premium_offline').'</p>';
if(!empty($grassRecord['key']))echo '<div class="chisimba-form-field"><label for="premium-code">'.$t('activation_code').'</label><input id="premium-code" type="text" readonly autocomplete="off" value="'.$e($grassRecord['key']).'"></div>';
// A normal POST intentionally renders the new code once; it never enters a redirect URL or JS storage.
echo '<form class="chisimba-form chisimba-flow" method="post" action="'.$e($this->uri(['action'=>'createpremiumkey'],'grassguide')).'">'.$hidden('csrf_token',$grassCsrf).$r->field('device_name',$grassInput??[],70,true).$r->button('create_premium_key','plus').'</form></section>';
echo '<section class="chisimba-flow"><h2>'.$t('premium_devices').'</h2>';
foreach($premium->keys() as $key)echo '<article class="chisimba-form-card chisimba-form-card--wide"><h3>'.$e($key['name']).'</h3><form method="post" action="'.$e($this->uri(['action'=>'revokepremiumkey'],'grassguide')).'">'.$hidden('csrf_token',$grassCsrf).$hidden('key_id',$key['id']).$r->button('revoke_premium_key','x').'</form></article>';
echo '</section>';
