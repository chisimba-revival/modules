<?php
require dirname(__DIR__).'/classes/shopcartsession.php';
function check($v,$m){if(!$v)throw new RuntimeException($m);}
$order=['id'=>'order-a','payment_state'=>'unpaid'];
$s=['shop_cart'=>['a'=>1],'shop_request'=>'old','shop_offer_seen'=>true];
ShopCartSession::remember($s,$order,$s['shop_cart']);
ShopCartSession::paid($s,$order);
check($s['shop_cart']===['a'=>1],'Unpaid cart preserved');
$order['payment_state']='paid';
$s['shop_cart']['a']++;$s['shop_cart']['b']=2;
ShopCartSession::paid($s,$order);
check($s['shop_cart']===['a'=>1,'b'=>2],'Only purchased copies removed; new additions preserved');
ShopCartSession::remember($s,$order,$s['shop_cart']);ShopCartSession::paid($s,$order);
check($s['shop_cart']===['a'=>1,'b'=>2],'Repeat receipt/prepare does not clear new shopping');
$s=['shop_cart'=>['a'=>1]];ShopCartSession::remember($s,$order,$s['shop_cart']);
ShopCartSession::edited($s,$s['shop_cart'],[]);$s['shop_cart']=['a'=>1];
ShopCartSession::paid($s,$order);check($s['shop_cart']===['a'=>1],'Removed and re-added product retained');
$s=['shop_cart'=>['a'=>1]];ShopCartSession::paid($s,$order);check($s['shop_cart']===['a'=>1],'Unrelated receipt never clears cart');
ShopCartSession::remember($s,$order,$s['shop_cart']);ShopCartSession::paid($s,$order);
check($s['shop_cart']===[],'Purchased cart emptied');
echo "PASS: verified cart completion, later additions, repeat visits and unrelated receipts\n";
