<?php
/** Explicit catalogue import; never runs on installation or a web request. */
if (PHP_SAPI!=='cli' || count($argv)!==3) {
    fwrite(STDERR,"Usage: php import-contributions.php APPLICATION_DIRECTORY CATALOGUE_JSON\n"); exit(1);
}
$rows=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR);
if(!is_array($rows)||!array_is_list($rows)||!$rows) throw new RuntimeException('Invalid catalogue');
foreach($rows as $row) {
    if(!is_array($row)||!is_int($row['baseMinor']??null)||$row['baseMinor']<1
        ||!is_int($row['vatBasisPoints']??null)||$row['vatBasisPoints']<0
        ||!is_int($row['totalMinor']??null)
        ||$row['totalMinor']!==$row['baseMinor']+intdiv($row['baseMinor']*$row['vatBasisPoints']+5000,10000))
        throw new RuntimeException('Invalid contribution amount');
}
chdir($argv[1]);
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;
require_once 'classes/core/engine_class_inc.php';
$engine=new engine();
$catalog=$engine->getObject('paymentcatalogservice','payment-service');
foreach($rows as $row) {
    $version='contribution-v1';
    foreach($catalog->productOptions() as $saved) {
        if($saved['code']===$row['code'] && ($saved['purpose_type']!=='contribution'
            ||$saved['purpose_id']!==$row['code']||$saved['billing_period']!=='one_off'
            ||$saved['name']!==$row['name']||empty($saved['active'])))
            throw new RuntimeException('Existing product differs: '.$row['code']);
    }
    $existing=$catalog->productVersion($row['code'],$version);
    if($existing && ($existing['purpose_type']!=='contribution'||$existing['billing_period']!=='one_off'
        ||(int)$existing['price']['amount_minor']!==$row['totalMinor']||$existing['price']['currency']!==$row['currency']))
        throw new RuntimeException('Existing product differs: '.$row['code']);
    $product=$catalog->createProduct(['code'=>$row['code'],'name'=>$row['name'],'purposeType'=>'contribution',
        'purposeId'=>$row['code'],'billingPeriod'=>'one_off']);
    if(empty($product['ok'])) throw new RuntimeException($product['code']);
    $price=$catalog->addPrice($product['productId'],['versionCode'=>$version,'amountMinor'=>$row['totalMinor'],'currency'=>$row['currency']]);
    if(empty($price['ok'])) throw new RuntimeException($price['code']);
    echo $row['code'].' '.$price['code']."\n";
}
