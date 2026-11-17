<?php
/** Opt-in real dbTable test; only random disposable tables, fake payments and mail. */
if (PHP_SAPI!=='cli' || getenv('SHOP_NOTIFICATION_DB_TEST')!=='1') exit(64);
$app=getenv('SHOP_TEST_FRAMEWORK_APP');
if (!$app || !is_file($app.'/classes/core/dbtable_class_inc.php')) throw new RuntimeException('Framework app required');
$host=getenv('SHOP_TEST_DB_HOST')?:'db';$password=getenv('SHOP_TEST_DB_PASSWORD');
$database='shop_notice_test_'.bin2hex(random_bytes(6));
// Schema setup/teardown is fixture-only. All shop operations below use real dbTable.
$setup=new PDO('mysql:host='.$host, 'root', $password, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$setup->exec('CREATE DATABASE '.$database.' CHARACTER SET utf8mb4');
try {
    $setup->exec('USE '.$database);
    foreach (glob(dirname(__DIR__).'/sql/tbl_shop_*.sql') as $schema) {
        require $schema;$columns=[];
        foreach ($fields as $name=>$definition) {
            $type=match($definition['type']){'integer'=>'INT','clob'=>'LONGTEXT',default=>'VARCHAR('.$definition['length'].')'};
            $columns[]=$name.' '.$type.(!empty($definition['notnull'])?' NOT NULL':' NULL').(isset($definition['default'])?' DEFAULT '.$setup->quote($definition['default']):'');
        }
        $columns[]='UNIQUE KEY (id)';
        $setup->exec('CREATE TABLE '.$tablename.' ('.implode(',',$columns).') ENGINE=InnoDB');
    }
    require_once __DIR__.'/fixtures.php';
    set_include_path($app.PATH_SEPARATOR.get_include_path());
    require_once $app.'/classes/core/chisimbadatabaseconnection.php';
    require_once $app.'/classes/core/dbtable_class_inc.php';
    require_once dirname(__DIR__).'/classes/shopstore_class_inc.php';
    $connection=ChisimbaDatabaseConnection::fromLegacyDsn(['phptype'=>'mysqli','hostspec'=>$host,'username'=>'root','password'=>$password,'database'=>$database]);
    $store=new shopstore;
    (new ReflectionProperty(dbTable::class,'_db'))->setValue($store,$connection);
    $store->dbLayer='PDO';$store->dbType='mysqli';$store->_tableName='tbl_shop_books';$store->_serverName='fixture';
    $store->add('settings',['id'=>'shop','revision'=>1,'settings_json'=>'{"enabled":false,"terms":"","zones":{}}']);
    $GLOBALS['shopNotificationTestStore']=$store;
    require __DIR__.'/paid_notifications_test.php';
    $before=$store->one('settings','shop');
    try {$store->transaction(function()use($store){$store->save('settings','shop',['settings_json'=>'{}']);throw new DomainException('rollback');});}
    catch(DomainException $error){if($error->getMessage()!=='rollback')throw $error;}
    if($store->one('settings','shop')!==$before)throw new RuntimeException('Settings rollback failed');
    echo "PASS: real PDO/dbTable notification snapshots, retries and transaction rollback\n";
} finally {
    $setup->exec('DROP DATABASE '.$database);
}
