<?php
/** Disposable audience_test_* database; no transport. */
require __DIR__.'/../../audience/tests/campaign_integration_test.php';
require __DIR__.'/../classes/publishingnewsfeed_class_inc.php';
$pdo->exec('CREATE TABLE tbl_simpleblog_posts (id VARCHAR(32),post_title VARCHAR(200),featured_image VARCHAR(2048),featured_alt VARCHAR(200),post_type VARCHAR(16),blogid VARCHAR(32),post_status VARCHAR(16),required_tier_code VARCHAR(32),published_at DATETIME,datecreated DATETIME)');
$insert=$pdo->prepare('INSERT INTO tbl_simpleblog_posts VALUES (?,?,?,?,?,?,?,?,?,?)');
foreach([['old','site','site','posted','',-5],['second','site','site','posted','',-2],['first','site','site','posted','',-1],['private','site','site','posted','paid',0],['draft','site','site','draft','',0],['course','context','course','posted','',0],['future','site','site','posted','',1]] as [$id,$type,$scope,$state,$tier,$day])$insert->execute([$id,$id,'/wp-content/uploads/photo.jpg','',$type,$scope,$state,$tier,gmdate('Y-m-d H:i:s',time()+$day*86400),gmdate('Y-m-d H:i:s',time()-864000)]);
class FeedFixture extends publishingnewsfeed{function selectPrepared($sql,$values=[]){return $this->selectExactPrepared($sql,$values);}}
$feed=new FeedFixture;$feed->init();$items=$feed->latest(2);
check(array_column($items,'title')===['first','second'],'Latest two exclude future, draft, course and tier-restricted posts');
check($items[0]['url']==='https://example.test/index.php?module=simpleblog&action=view&id=first','Valid public post route');check($items[0]['image']['url']==='https://example.test/wp-content/uploads/photo.jpg','Legacy public featured image');
echo "PASS latest public site news selection, ordering, links and imported featured images.\n";
