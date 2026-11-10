<?php
/** Reusable integer-credit ledger. Trusted server callers only; no HTTP grant endpoint. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('No direct access'); }
class creditservice extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_credit_accounts',$pearDb,$errorCallback); }
    private function key($scope,$reference) { return hash('sha256',$scope."\0".$reference); }
    private function lock($scope,$owner)
    {
        foreach ([$scope,$owner] as $v) if(!is_string($v)||!preg_match('/^[A-Za-z0-9_.:-]{1,64}$/D',$v)) throw new InvalidArgumentException('Invalid credit account');
        $id=$this->key($scope,$owner);
        $this->executeExactPrepared('INSERT INTO tbl_credit_accounts (id,scope,owner_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE id=id',[$id,$scope,$owner]);
        $this->selectExactPrepared('SELECT id FROM tbl_credit_accounts WHERE id=? FOR UPDATE',[$id]);
        return $id;
    }
    private function reference($value)
    { if(!is_string($value)||$value===''||strlen($value)>200||preg_match('/[\x00-\x1f]/',$value))throw new InvalidArgumentException('Invalid credit reference'); return $value; }
    /** Immutable, idempotent grant. Paid sources must be verified by payment-service first.
     * Use a globally unique source per scope (store + transaction/cycle), never a device request ID.
     * Subscription renewal grants specify the verified paid-through UTC timestamp.
     */
    public function grant($scope,$owner,$source,$units,$kind='trial',$plan=null,$validUntil=null)
    {
        $this->reference($source);
        if(!is_int($units)||$units<1||$units>1000000||!in_array($kind,['trial','monthly','topup','adjustment'],true))throw new InvalidArgumentException('Invalid credit grant');
        if($kind==='monthly'){
            $d=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',(string)$validUntil,new DateTimeZone('UTC'));
            if(!is_string($plan)||!preg_match('/^[a-z][a-z0-9_-]{0,31}$/D',$plan)||!$d||$d->format('Y-m-d H:i:s')!==$validUntil)throw new InvalidArgumentException('Invalid credit period');
        }elseif($plan!==null||$validUntil!==null)throw new InvalidArgumentException('Unexpected credit period');
        return $this->transactionWithSavepoint(function()use($scope,$owner,$source,$units,$kind,$plan,$validUntil){
            $account=$this->lock($scope,$owner);$id=$this->key($scope,'grant:'.$source);
            $fp=hash('sha256',json_encode([$account,$units,$kind,$plan,$validUntil],JSON_THROW_ON_ERROR));
            $old=$this->selectExactPrepared('SELECT fingerprint FROM tbl_credit_entries WHERE id=?',[$id]);
            if($old){if($old[0]['fingerprint']!==$fp)throw new DomainException('credit_source_conflict');return $id;}
            $this->executeExactPrepared('INSERT INTO tbl_credit_entries (id,account_id,amount,kind,fingerprint,plan_code,valid_until,created_at) VALUES (?,?,?,?,?,?,?,?)',[$id,$account,$units,$kind,$fp,$plan,$validUntil,gmdate('Y-m-d H:i:s')]);
            return $id;
        });
    }
    /** A refund removes the original grant even if spent, leaving debt rather than free use.
     * The original grant must be recorded first, even for an out-of-order refund notification.
     */
    public function reverse($scope,$owner,$source)
    {
        $this->reference($source);
        return $this->transactionWithSavepoint(function()use($scope,$owner,$source){
            $account=$this->lock($scope,$owner);$original=$this->key($scope,'grant:'.$source);$id=$this->key($scope,'reverse:'.$source);
            $row=$this->selectExactPrepared('SELECT amount,account_id FROM tbl_credit_entries WHERE id=?',[$original])[0]??null;
            if(!$row||$row['account_id']!==$account)throw new DomainException('credit_source_missing');
            if($this->selectExactPrepared('SELECT id FROM tbl_credit_entries WHERE id=?',[$id]))return;
            $this->executeExactPrepared('INSERT INTO tbl_credit_entries (id,account_id,amount,kind,fingerprint,reversal_of,created_at) VALUES (?,?,?,?,?,?,?)',[$id,$account,-(int)$row['amount'],'reversal',hash('sha256',$original),$original,gmdate('Y-m-d H:i:s')]);
        });
    }
    private function totals($account)
    {
        $balance=(int)$this->selectExactPrepared('SELECT COALESCE(SUM(amount),0) AS n FROM tbl_credit_entries WHERE account_id=?',[$account])[0]['n'];
        $held=(int)$this->selectExactPrepared("SELECT COUNT(*) AS n FROM tbl_credit_holds WHERE account_id=? AND state='held'",[$account])[0]['n'];
        return ['balance'=>$balance,'held'=>$held,'available'=>max(0,$balance-$held),'debt'=>max(0,-$balance)];
    }
    public function snapshot($scope,$owner)
    {
        return $this->transactionWithSavepoint(function()use($scope,$owner){
            $account=$this->lock($scope,$owner);$out=$this->totals($account);
            $plans=$this->selectExactPrepared("SELECT e.plan_code,e.valid_until FROM tbl_credit_entries e WHERE e.account_id=? AND e.kind='monthly' AND e.valid_until>? AND NOT EXISTS (SELECT 1 FROM tbl_credit_entries r WHERE r.reversal_of=e.id) ORDER BY e.valid_until DESC,e.created_at DESC,e.id DESC",[$account,gmdate('Y-m-d H:i:s')]);
            $out['plan']=$plans[0]['plan_code']??'free';$out['paid_through']=$plans[0]['valid_until']??null;
            return $out;
        });
    }
    public function reserve($scope,$owner,$reference)
    {
        $this->reference($reference);
        return $this->transactionWithSavepoint(function()use($scope,$owner,$reference){
            $account=$this->lock($scope,$owner);$id=$this->key($scope,'hold:'.$reference);
            $old=$this->selectExactPrepared('SELECT account_id,state FROM tbl_credit_holds WHERE id=?',[$id]);
            if($old){if($old[0]['account_id']!==$account||$old[0]['state']==='released')throw new DomainException('credit_reservation_conflict');return;}
            if($this->totals($account)['available']<1)throw new DomainException('insufficient_credits');
            $now=gmdate('Y-m-d H:i:s');
            $this->executeExactPrepared('INSERT INTO tbl_credit_holds (id,account_id,state,created_at,updated_at) VALUES (?,?,?,?,?)',[$id,$account,'held',$now,$now]);
        });
    }
    /** Missing holds are pre-credit jobs: never retroactively charge them. */
    public function settle($scope,$owner,$reference,$success)
    {
        $this->reference($reference);if(!is_bool($success))throw new InvalidArgumentException('Invalid settlement');
        return $this->transactionWithSavepoint(function()use($scope,$owner,$reference,$success){
            $account=$this->lock($scope,$owner);$id=$this->key($scope,'hold:'.$reference);
            $row=$this->selectExactPrepared('SELECT account_id,state FROM tbl_credit_holds WHERE id=?',[$id])[0]??null;
            if(!$row)return;
            if($row['account_id']!==$account)throw new DomainException('credit_reservation_conflict');
            if($row['state']!=='held')return; // Terminal: deleting a result never refunds a spent credit.
            if($success)$this->executeExactPrepared('INSERT INTO tbl_credit_entries (id,account_id,amount,kind,fingerprint,created_at) VALUES (?,?,?,?,?,?)',[$this->key($scope,'spend:'.$reference),$account,-1,'spend',hash('sha256',$id),gmdate('Y-m-d H:i:s')]);
            $this->executeExactPrepared('UPDATE tbl_credit_holds SET state=?,updated_at=? WHERE id=?',[$success?'spent':'released',gmdate('Y-m-d H:i:s'),$id]);
        });
    }
}
