<?php
/** Administrator-owned subscriber edits; all transitions preserve consent history. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class audienceadmin extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_audience_contacts',$pearDb,$errorCallback);}
 public function allowed(){ $u=$this->getObject('user','security');return $u->isLoggedIn()&&$u->isAdmin(); }
 public function guard(){if(!$this->allowed())throw new DomainException('forbidden');}
 public function q($s){return $s===''?"''":$this->objEngine->getDbObj()->quote($s);}
 public function rows($sql){$r=$this->objEngine->getDbObj()->query($sql);if(PEAR::isError($r))throw new RuntimeException('storage');return $r->fetchAll(MDB2_FETCHMODE_ASSOC);}
 public function execute($sql){$r=$this->objEngine->getDbObj()->exec($sql);if(PEAR::isError($r))throw new RuntimeException('storage');return $r;}
 public function search($query,$state,$page){
  $this->guard();$where=' WHERE 1=1';
  if($query!==''){$v=$this->q('%'.mb_substr($query,0,200).'%');$where.=" AND (name LIKE $v OR email LIKE $v)";}
  if(in_array($state,['subscribed','unsubscribed','pending','suppressed'],true))$where.=' AND state='.$this->q($state);
  $count=(int)$this->rows('SELECT COUNT(*) AS n FROM tbl_audience_contacts'.$where)[0]['n'];
  $page=max(1,min((int)$page,max(1,(int)ceil($count/40))));
  return ['count'=>$count,'page'=>$page,'rows'=>$this->rows('SELECT * FROM tbl_audience_contacts'.$where.' ORDER BY name,email LIMIT 40 OFFSET '.(($page-1)*40))];
 }
 public function save($id,array $input){
  $this->guard();$service=$this->getObject('audienceservice','audience');$email=audienceservice::email($input['email']??'');$name=trim($input['name']??'');
  if($name===''||mb_strlen($name)>200)throw new DomainException('invalid');
  $state=$input['state']??'';if(!in_array($state,['subscribed','unsubscribed','suppressed','pending'],true))throw new DomainException('invalid');
  $this->execute('START TRANSACTION');
  try{
   $row=$service->lock($id);if(!$row)throw new DomainException('invalid');
   if((int)$row['revision']!==(int)($input['revision']??-1))throw new DomainException('conflict');
   if($email!==$row['email']&&$this->rows('SELECT id FROM tbl_audience_contacts WHERE email='.$this->q($email)))throw new DomainException('duplicate');
   $renew=$state==='subscribed'&&($row['state']!=='subscribed'||$email!==$row['email']);
   $evidence=trim($input['evidence']??'');
   if($renew&&(($input['consent']??'')!=='1'||$evidence===''))throw new DomainException('consent_required');
   $sets='name='.$this->q($name).',email='.$this->q($email).',state='.$this->q($state).',revision=revision+1';
   if($email!==$row['email']){
    $sets.=',verified_at=NULL';
    $this->execute('DELETE FROM tbl_audience_tokens WHERE contact_id='.$this->q($id));
   }
   $this->execute('UPDATE tbl_audience_contacts SET '.$sets.' WHERE id='.$this->q($id));
   $actor=$this->getObject('user','security')->userId();
   if($this->insert(['id'=>bin2hex(random_bytes(16)),'contact_id'=>$id,'action'=>'admin_edit','source'=>'admin:'.$actor,'recorded_at'=>gmdate('Y-m-d H:i:s'),'wording'=>json_encode(['old_state'=>$row['state'],'new_state'=>$state,'email_changed'=>$row['email']!==$email,'consent_evidence'=>mb_substr($evidence,0,600)],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)],'tbl_audience_consent')===false)throw new RuntimeException('storage');
   $this->execute('COMMIT');return $service->one($id);
  }catch(Throwable $e){$this->execute('ROLLBACK');throw $e;}
 }
}
