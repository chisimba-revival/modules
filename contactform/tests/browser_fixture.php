<?php
if(PHP_SAPI!=='cli'||getenv('CONTACT_TEST_SITE')!=='/var/www/html/ch')exit(64);
chdir(getenv('CONTACT_TEST_SITE'));$config=simplexml_load_file('config/config.xml');$host=parse_url((string)$config->KEWL_SITE_ROOT,PHP_URL_HOST);
if(!in_array($host,['chisimba.test','localhost','127.0.0.1'],true))throw new RuntimeException('Local only');
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']=$host;$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';$_SERVER['REMOTE_ADDR']='192.0.2.10';
$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$engine=new engine;
$db=$engine->getDbObj();$id='c07ac7f0a123456789abcdef01234567';
if(($argv[1]??'')==='remove'){
 foreach(['review','messages'] as $table)$db->exec('DELETE FROM tbl_contactform_'.$table.' WHERE id='.$db->quote($id));
 echo "Removed synthetic browser fixture\n";exit;
}
$store=$engine->getObject('contactstore','contactform');
if(!$store->find($id))$store->transaction(function()use($store,$id){$store->insert(['id'=>$id,'name'=>'Sample reader','email'=>'browser-fixture@example.invalid','subject'=>'Synthetic book enquiry','message'=>"Hello,\nCould you quote for five books?\nThank you.",'fingerprint'=>str_repeat('a',64),'status'=>'preview','datecreated'=>gmdate('Y-m-d H:i:s'),'datemodified'=>gmdate('Y-m-d H:i:s')]);});
if(!$store->find($id))throw new RuntimeException('Fixture creation failed');
echo "Created synthetic browser fixture; no mail sent\n";
