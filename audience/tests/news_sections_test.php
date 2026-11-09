<?php
require __DIR__.'/message_heading_test.php';
class ChisimbaObject{function getObject($n,$m){return $GLOBALS['services'][$n];}}
require __DIR__.'/../classes/audiencenews_class_inc.php';
$GLOBALS['services']['audiencenews']=new audiencenews;
$items=[['title'=>'First <story>','url'=>'https://example.test/one','image'=>['url'=>'https://example.test/one.jpg']],['title'=>'Second','url'=>'https://example.test/two','image'=>null]];
$r=['payload'=>json_encode(['body'=>'Message','latest_news'=>true,'news'=>$items,'support'=>true])];
$GLOBALS['services']['dbsysconfig']->heading='Support';$html=$c->composeHtml($r);$text=$c->compose($r);
check(str_contains($html,'First &lt;story&gt;')&&substr_count($html,'<img')===1,'News cards escape titles and omit absent image');
check(strpos($html,'https://example.test/two')<strrpos($html,'<h2>Support</h2>'),'News precedes support');
check(str_contains($text,"First <story>\nhttps://example.test/one"),'Plain text contains news title and link');
$r['payload']=json_encode(['preview'=>'Old preview','preview_html'=>'Old HTML','latest_news'=>true,'news'=>$items]);check($c->compose($r)==='Old preview'&&$c->composeHtml($r)==='Old HTML','Saved previews remain frozen');
check($GLOBALS['services']['audiencenews']->html([])==='','No empty news section');
echo "PASS newsletter news order, linked images, escaped titles, text alternative and frozen preview.\n";
