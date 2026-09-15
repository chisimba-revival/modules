<?php
/** Idempotent LTB staging catalogue setup; never enables payment or changes existing prices. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']=$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com')throw new RuntimeException('Protected staging only');
$catalog=$e->getObject('paymentcatalogservice','payment-service');
foreach(array(array('ltb-contributing','Contributing Payment',11500,1500),array('ltb-gold','Gold Contribution',23000,3000),array('ltb-platinum','Platinum Contribution',57500,7500)) as [$code,$name,$total,$vat]){
    $r=$catalog->createProduct(array('code'=>$code,'name'=>$name,'purposeType'=>'contribution','purposeId'=>'learnthebirds','billingPeriod'=>'one_off'));
    if(empty($r['ok']))throw new RuntimeException('Product setup failed');
    $v=$catalog->productVersion($code,'2026-09-vat15');
    if($v&&($v['name']!==$name||$v['purpose_type']!=='contribution'||$v['billing_period']!=='one_off'||(int)$v['price']['amount_minor']!==$total||(int)$v['price']['vat_minor']!==$vat||$v['price']['currency']!=='ZAR'))throw new RuntimeException('Existing catalogue differs; review without overwriting');
    $p=$catalog->addPrice($r['productId'],array('versionCode'=>'2026-09-vat15','amountMinor'=>$total,'vatMinor'=>$vat,'currency'=>'ZAR','effectiveFrom'=>'2026-09-15 00:00:00'));
    if(empty($p['ok']))throw new RuntimeException('Price setup failed');
    echo $name.': ZAR '.number_format($total/100,2).' including VAT '.number_format($vat/100,2)."\n";
}
echo "No payment credentials, enablement, accounts or subscriptions changed.\n";
