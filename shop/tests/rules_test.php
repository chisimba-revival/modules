<?php
require_once dirname(__DIR__).'/classes/shoprules.php';
$checks=0;
$assert=function($ok,$message)use(&$checks){++$checks;if(!$ok)throw new RuntimeException($message);};
$reject=function($call,$expected)use($assert){try{$call();throw new RuntimeException('Expected '.$expected);}catch(DomainException $e){$assert($e->getMessage()===$expected,'Unexpected rejection '.$e->getMessage());}};
// Illustrative figures are fixtures only, never installed as real shipping prices.
$policy=['revision'=>1,'zones'=>['ZA'=>['enabled'=>true,'max_quantity'=>20,'bands'=>[
 ['from'=>1,'amount_minor'=>11000],['from'=>5,'amount_minor'=>5000],['from'=>10,'amount_minor'=>0]]]]];
foreach([1=>11000,2=>11000,4=>11000,5=>5000,9=>5000,10=>0,20=>0] as $quantity=>$expected)$assert(ShopRules::shipping('ZA',$quantity,$policy)===$expected,'Wrong threshold '.$quantity);
$reject(fn()=>ShopRules::shipping('US',5,$policy),'country_unavailable');
$reject(fn()=>ShopRules::shipping('ZA',21,$policy),'quantity_unavailable');
$reject(fn()=>ShopRules::shipping('ZA',1,[]),'shipping_unconfigured');
$reject(fn()=>ShopRules::bands([]),'invalid_shipping');
$reject(fn()=>ShopRules::bands([['from'=>5,'amount_minor'=>0]]),'invalid_shipping');
$reject(fn()=>ShopRules::bands([['from'=>1,'amount_minor'=>0],['from'=>5,'amount_minor'=>100]]),'shipping_must_decrease');
$reject(fn()=>ShopRules::bands([['from'=>1,'amount_minor'=>100],['from'=>1,'amount_minor'=>50]]),'invalid_shipping');
foreach(['-1','1.001','1e2','1,50','NaN'] as $bad)$reject(fn()=>ShopRules::money($bad),'invalid_money');
$assert(ShopRules::money('110.50')===11050,'Exact cents');
$a=str_repeat('a',32);$b=str_repeat('b',32);
$books=[$a=>['title'=>'Book A','isbn'=>'A','revision'=>1,'status'=>'published','price_minor'=>12500],$b=>['title'=>'Book B','isbn'=>'B','revision'=>2,'status'=>'published','price_minor'=>20000]];
$q=ShopRules::quote([$a=>2,$b=>3],$books,'ZA',$policy);
$assert($q['quantity']===5&&$q['shipping_minor']===5000&&$q['subtotal_minor']===85000&&$q['amount_minor']===90000,'Mixed titles use total copies and one delivery charge');
$assert(ShopRules::quote([$a=>10],$books,'ZA',$policy)['amount_minor']===125000,'Free shipping does not discount books');
$reject(fn()=>ShopRules::quote([$a=>0],$books,'ZA',$policy),'empty_cart');
$reject(fn()=>ShopRules::cart([$a=>101]),'invalid_number');
$reject(fn()=>ShopRules::cart([$a=>60,$b=>60]),'quantity_unavailable');
$books[$a]['status']='draft';$reject(fn()=>ShopRules::quote([$a=>1],$books,'ZA',$policy),'book_unavailable');
echo "PASS: $checks quantity, destination, money and cart rules\n";
