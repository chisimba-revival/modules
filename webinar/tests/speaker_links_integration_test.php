<?php
/** Isolated audience_test_* database only; no transport. */
require __DIR__.'/../../audience/tests/campaign_integration_test.php';
require $framework.'/app/core_modules/userdetails/classes/authorbiographyvalue_class_inc.php';
require __DIR__.'/../classes/speakerlinks_class_inc.php';
$pdo->exec('CREATE TABLE tbl_webinar_speaker_links (id VARCHAR(32) PRIMARY KEY,speaker_id VARCHAR(32) NOT NULL,slot INT NOT NULL,category VARCHAR(24) NOT NULL,label VARCHAR(80) NOT NULL,url VARCHAR(2048) NOT NULL, UNIQUE(speaker_id,slot)) ENGINE=InnoDB');
$pdo->exec('CREATE TABLE tbl_webinar_records (id VARCHAR(32) PRIMARY KEY,kind VARCHAR(16),status VARCHAR(16)) ENGINE=InnoDB');
$speaker=str_repeat('b',32);$pdo->prepare('INSERT INTO tbl_webinar_records VALUES (?, ?, ?)')->execute([$speaker,'speaker','published']);
$GLOBALS['objects']['webinareditpolicy']=new class{public $allowed=true;function canManage(){return $this->allowed;}};
$GLOBALS['objects']['webinareditstore']=new class{function record($id,$lock=false){$s=$GLOBALS['pdo']->prepare('SELECT * FROM tbl_webinar_records WHERE id=?'.($lock?' FOR UPDATE':''));$s->execute([$id]);return $s->fetch(PDO::FETCH_ASSOC)?:null;}};
class LinkFixture extends speakerlinks{function selectPrepared($sql,$values=[]){return $this->selectExactPrepared($sql,$values);}function delete($key,$value){$s=$GLOBALS['pdo']->prepare('DELETE FROM tbl_webinar_speaker_links WHERE id=?');return $s->execute([$value]);}}
$links=new LinkFixture;$links->init();$rows=[];
$base=['category'=>'website','url'=>'https://example.test/','label'=>'My site'];
for($i=0;$i<4;$i++)$rows=$links->change($speaker,'save',$base+['link_id'=>bin2hex(random_bytes(16)),'links_version'=>speakerlinks::revision($rows)]);
check(count($rows)===4,'Four links saved');
reject(fn()=>$links->change($speaker,'save',$base+['link_id'=>bin2hex(random_bytes(16)),'links_version'=>speakerlinks::revision($rows)]),'links_limit');
reject(fn()=>$links->change($speaker,'delete',['link_id'=>$rows[0]['id'],'links_version'=>'stale']),'editor_conflict');
foreach(['javascript:alert(1)','http://example.test','https://name:password@example.test'] as $url)reject(fn()=>$links->change($speaker,'save',['url'=>$url,'category'=>'website','link_id'=>$rows[0]['id'],'links_version'=>speakerlinks::revision($rows)]),'links_invalid');
$GLOBALS['objects']['webinareditpolicy']->allowed=false;reject(fn()=>$links->change($speaker,'delete',['link_id'=>$rows[0]['id'],'links_version'=>speakerlinks::revision($rows)]),'editor_forbidden');$GLOBALS['objects']['webinareditpolicy']->allowed=true;
$rows=$links->change($speaker,'save',['url'=>'https://example.test/new','category'=>'podcast','label'=>'Listen','link_id'=>$rows[0]['id'],'links_version'=>speakerlinks::revision($rows)]);check($rows[0]['label']==='Listen','Edit updates existing link');
$rows=$links->change($speaker,'delete',['link_id'=>$rows[1]['id'],'links_version'=>speakerlinks::revision($rows)]);check(count($rows)===3,'Delete only chosen link');
$rows=$links->change($speaker,'save',$base+['link_id'=>bin2hex(random_bytes(16)),'links_version'=>speakerlinks::revision($rows)]);check(count($rows)===4,'Vacant slot reusable');
$pdo->prepare('UPDATE tbl_webinar_records SET status=? WHERE id=?')->execute(['draft',$speaker]);$GLOBALS['objects']['webinareditpolicy']->allowed=false;check($links->listing($speaker)===[],'Draft links private');
echo "PASS speaker links: limit, edit, remove, slot reuse, stale revisions, URL safety, permissions and draft privacy.\n";
