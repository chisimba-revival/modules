<?php
/** Remove only the named synthetic browser fixture on the local development site. */
if(PHP_SAPI!=='cli'||getenv('SHOP_TEST_SITE')!=='/var/www/html/ch')exit(64);
chdir(getenv('SHOP_TEST_SITE'));$x=simplexml_load_file('config/config.xml');
if(!in_array(parse_url((string)$x->KEWL_SITE_ROOT,PHP_URL_HOST),['chisimba.test','localhost','127.0.0.1'],true))exit(64);
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$engine=new engine();$store=$engine->getObject('shopstore','shop');$db=$engine->getDbObj();
$id=$argv[1]??'';if(!preg_match('/^[a-f0-9]{32}$/D',$id))exit(64);
$store->transaction(function()use($store,$db,$id){
 $book=$store->one('books',$id);
 if(!$book||$book['title']!=='Disposable Shop Browser QA')throw new RuntimeException('Not the browser fixture');
 foreach($store->rows('orders') as $order)foreach(json_decode($order['snapshot'],true)['lines'] as $line)if($line['book_id']===$id)throw new RuntimeException('Fixture has orders; review before cleanup');
 $settings=$store->one('settings','shop');$data=json_decode($settings['settings_json'],true);
 if($data['terms']!=='Synthetic browser QA settings only. Not real selling terms.'||$data['enabled'])throw new RuntimeException('Settings changed; do not overwrite');
 $result=$db->exec('DELETE FROM tbl_shop_books WHERE id='.$db->quote($id,'text'));
 if($result===false||PEAR::isError($result))throw new RuntimeException('Cleanup failed');
 $store->save('settings','shop',['settings_json'=>json_encode(['enabled'=>false,'terms'=>'','zones'=>[]]),'revision'=>1]);
});
echo "PASS: synthetic browser book/rates removed; checkout remains disabled\n";
