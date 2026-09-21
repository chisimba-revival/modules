<?php
/** Pending-registration retention. Never deletes or modifies canonical accounts. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('No direct access'); }
class registrationcleanup extends ChisimbaObject
{
    private function db() { return $this->objEngine->getDbObj(); }
    private function sql($sql, array $args = array(), $read = true)
    {
        $statement = $this->db()->prepare($sql, null, $read ? MDB2_PREPARE_RESULT : MDB2_PREPARE_MANIP);
        if (PEAR::isError($statement)) { throw new RuntimeException('Cleanup query unavailable.'); }
        $result = $statement->execute($args); $statement->free();
        if (PEAR::isError($result)) { throw new RuntimeException('Cleanup query failed.'); }
        if (!$read) { return $result; }
        $rows = $result->fetchAll(MDB2_FETCHMODE_ASSOC); $result->free();
        if (PEAR::isError($rows)) { throw new RuntimeException('Cleanup read failed.'); }
        return $rows;
    }
    private function admin()
    {
        $user = $this->getObject('user', 'security');
        if (!$user->isAdmin()) { throw new RuntimeException('Administrator access required.'); }
        return (string) $user->userId();
    }
    public function review($id)
    {
        $this->admin();
        if (!preg_match('/^[a-f0-9]{32}$/D', (string) $id)) { return null; }
        $rows = $this->sql('SELECT * FROM tbl_registration_service_pending WHERE id=?', array($id));
        if (!$rows) { return null; }
        $row = $rows[0];
        $row['protected'] = $this->protected($row);
        return $row;
    }
    private function protected(array $row)
    {
        if (!empty($row['provisioned_user_id']) || !empty($row['payment_product_code'])
            || !empty($row['verified_at']) || in_array($row['status'], array('verified','provisioned'), true)) { return true; }
        $users = $this->getObject('userservice', 'security');
        return !$users->usernameAvailable($row['username']) || !$users->emailAvailable($row['email_address']);
    }
    public function dismiss($id)
    {
        return $this->transition($id, 'dismissed', $this->admin());
    }
    /** CLI preview contains counts only; personal details are restricted to the admin page. */
    public function preview($details = false)
    {
        if ($details || PHP_SAPI !== 'cli') { $this->admin(); }
        $rows = $this->candidates(200);
        $summary = array('expire'=>0,'redact'=>0,'protected'=>0,'scanned'=>count($rows),'limit'=>200);
        $display = array();
        foreach ($rows as $row) {
            $held = $this->protected($row);
            $operation = $held ? 'protected' : (in_array($row['status'],array('dismissed','expired'),true) ? 'redact' : 'expire');
            $summary[$operation]++;
            if ($details) { $display[] = array_intersect_key($row, array_flip(array('id','first_name','surname','email_address','expires_at','status'))) + array('operation'=>$operation); }
        }
        // Linked and verified rows are deliberately excluded from automatic candidates.
        $held = $this->sql("SELECT COUNT(*) AS total FROM tbl_registration_service_pending WHERE status IN ('awaiting_legal_acceptance','awaiting_verification') AND expires_at<=? AND (COALESCE(provisioned_user_id,'')<>'' OR COALESCE(payment_product_code,'')<>'' OR verified_at IS NOT NULL)", array(date('Y-m-d H:i:s')));
        $summary['protected'] += (int) $held[0]['total'];
        if ($details) { $summary['rows'] = $display; }
        return $summary;
    }
    private function candidates($limit)
    {
        $cutoff = date('Y-m-d H:i:s', time() - 86400 * $this->getObject('registrationguard')->retentionDays());
        return $this->sql("SELECT * FROM tbl_registration_service_pending WHERE COALESCE(provisioned_user_id,'')='' AND COALESCE(payment_product_code,'')='' AND verified_at IS NULL AND ((status IN ('awaiting_legal_acceptance','awaiting_verification') AND expires_at<=?) OR (status IN ('dismissed','expired') AND updated_at<=?)) ORDER BY updated_at,id LIMIT " . (int)$limit,
            array(date('Y-m-d H:i:s'), $cutoff));
    }
    public function run()
    {
        if (PHP_SAPI !== 'cli') { throw new RuntimeException('Scheduled cleanup is CLI only.'); }
        if (!$this->getObject('registrationguard')->automatic()) { return array('enabled'=>false); }
        $summary = array('enabled'=>true,'expired'=>0,'redacted'=>0,'protected'=>0,'failed'=>0);
        foreach ($this->candidates(200) as $row) {
            $target = in_array($row['status'],array('dismissed','expired'),true) ? 'redacted' : 'expired';
            try {
                $result = $this->transition($row['id'], $target, null);
                if ($result === 'changed') { $summary[$target]++; }
                elseif ($result === 'protected') { $summary['protected']++; }
            } catch (Throwable $exception) { $summary['failed']++; }
        }
        $this->getObject('nativeauthwebcomposition','security')->build()['abuse']->purgeExpired();
        return $summary;
    }
    private function transition($id, $target, $actor)
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', (string)$id)) { return 'unavailable'; }
        $db = $this->db();
        if ($db->in_transaction) { throw new RuntimeException('Cleanup requires its own transaction.'); }
        if (PEAR::isError($db->beginTransaction())) { throw new RuntimeException('Cleanup transaction unavailable.'); }
        try {
            $rows = $this->sql('SELECT * FROM tbl_registration_service_pending WHERE id=? FOR UPDATE',array($id));
            if (!$rows) { $db->rollback(); return 'unavailable'; }
            $row = $rows[0]; $now = date('Y-m-d H:i:s');
            if ($this->protected($row)) { $db->rollback(); return 'protected'; }
            if ($target === 'redacted') {
                $cutoff = date('Y-m-d H:i:s',time()-86400*$this->getObject('registrationguard')->retentionDays());
                if (!in_array($row['status'],array('dismissed','expired'),true) || $row['updated_at'] > $cutoff) { $db->rollback(); return 'unavailable'; }
                // Keep only opaque reference, status and timestamps; no name-based decisions.
                $this->sql("UPDATE tbl_registration_service_pending SET status='redacted', username='',email_address='',first_name='',surname='',mobile_number='',identity_document_type='',identity_document_number='',password_hash=NULL,updated_at=? WHERE id=?",array($now,$id),false);
            } else {
                if (!in_array($row['status'],array('awaiting_legal_acceptance','awaiting_verification'),true)
                    || ($target === 'expired' && $row['expires_at'] > $now)) { $db->rollback(); return 'unavailable'; }
                $this->sql('UPDATE tbl_registration_service_pending SET status=?,password_hash=NULL,expires_at=LEAST(expires_at,?),updated_at=? WHERE id=?',array($target,$now,$now,$id),false);
            }
            // Verification locks the same pending row before changing state. State and expiry
            // make all outstanding verification links unusable, without a token-lock inversion.
            $event = $this->getObject('accounteventservice','account-event-service')->append(array(
                'eventType'=>'registration.'.$target,'subjectType'=>'pending_registration','subjectId'=>$id,
                'actorType'=>$actor === null ? 'service' : 'user','actorId'=>$actor ?? 'registration-cleanup',
                'outcome'=>'succeeded','reasonCode'=>$target === 'dismissed' ? 'administrator_review' : 'retention_policy',
                'correlationId'=>$row['correlation_id'],'sourceService'=>'registration-service','metadata'=>array()));
            if (empty($event['ok'])) { throw new RuntimeException('Cleanup audit failed.'); }
            if (PEAR::isError($db->commit())) { throw new RuntimeException('Cleanup commit failed.'); }
            return 'changed';
        } catch (Throwable $exception) {
            if ($db->in_transaction) { $db->rollback(); }
            throw $exception;
        }
    }
}
