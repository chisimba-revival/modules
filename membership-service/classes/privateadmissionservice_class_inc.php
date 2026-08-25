<?php
/** Canonical private-course admission orchestration. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class privateadmissionservice extends dbTable
{
    private const TABLE_NAME = 'tbl_membership_service_admissions';
    private const REVIEW = array('awaiting_payment', 'payment_under_review', 'ready_for_admission', 'declined');
    private const PAYMENT = array('not_recorded', 'pending', 'paid', 'failed', 'refunded', 'disputed');

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : self::TABLE_NAME, $pearDb, $errorCallback);
        $this->users = $this->getObject('userservice', 'security');
        $this->identity = $this->getObject('identityservice', 'security');
        $this->groups = $this->getObject('groupservice', 'groupadmin');
        $this->contexts = $this->getObject('dbcontext', 'context');
        $this->entitlements = $this->getObject('entitlementservice', 'entitlement-service');
        $this->events = $this->getObject('accounteventservice', 'account-event-service');
        $this->actor = $this->getObject('user', 'security');
    }

    public function createReview(array $input)
    {
        $course = $this->privateCourse($input['courseCode'] ?? '');
        $user = $this->user($input['userId'] ?? '');
        $status = $this->enum($input['reviewStatus'] ?? 'awaiting_payment', self::REVIEW);
        $payment = $this->enum($input['paymentStatus'] ?? 'not_recorded', self::PAYMENT);
        $correlation = $this->identifier($input['correlationId'] ?? '', 64);
        $actor = $this->actorDescriptor($input['actorType'] ?? 'user', $input['actorId'] ?? null);
        if ($course === null || $user === null || $status === null || $payment === null || $correlation === null) {
            return $this->result(false, 'invalid_admission_review');
        }
        $existing = $this->latest($course['contextcode'], $user['userid']);
        if ($existing !== null && !in_array($existing['review_status'], array('declined', 'revoked'), true)) {
            return $this->result(false, 'admission_already_exists', $existing['id']);
        }
        $now = date('Y-m-d H:i:s');
        $id = bin2hex(random_bytes(16));
        $row = array(
            'id' => $id, 'course_code' => $course['contextcode'], 'user_id' => $user['userid'],
            'admission_mode' => $course['private_admission_mode'] ?: 'manual_review',
            'review_status' => $status, 'payment_status' => $payment,
            'payment_reference' => $this->text($input['paymentReference'] ?? '', 191, true),
            'reason' => $this->text($input['reason'] ?? '', 4000, true),
            'entitlement_grant_id' => null, 'student_membership_added' => 0,
            'created_by' => $actor['id'], 'admitted_by' => null,
            'revoked_by' => null, 'correlation_id' => $correlation,
            'created_at' => $now, 'updated_at' => $now, 'admitted_at' => null, 'revoked_at' => null,
        );
        $this->beginTransaction();
        if ($this->insert($row) === false || !$this->audit('private_admission.review_created', $row, 'requested', $actor)) {
            $this->rollbackTransaction();
            return $this->result(false, 'admission_review_failed');
        }
        $this->commitTransaction();
        return $this->result(true, 'admission_review_created', $id);
    }

    /** Idempotent bridge used only after Payment Service verifies provider success. */
    public function admitConfirmedPayment($courseCode, $userId, $paymentReference, $correlationId)
    {
        $course = $this->privateCourse($courseCode);
        if ($course === null || (string) ($course['private_admission_mode'] ?? '') !== 'automatic_payment') {
            return $this->result(false, 'automatic_payment_not_configured');
        }
        $existing = $this->latest($course['contextcode'], $userId);
        if ($existing !== null && $existing['review_status'] === 'admitted') {
            return $this->result(true, 'already_admitted', $existing['id']);
        }
        if ($existing === null || in_array($existing['review_status'], array('declined', 'revoked'), true)) {
            $created = $this->createReview(array(
                'courseCode' => $course['contextcode'], 'userId' => $userId,
                'reviewStatus' => 'ready_for_admission', 'paymentStatus' => 'paid',
                'paymentReference' => $paymentReference,
                'reason' => 'Verified provider payment', 'correlationId' => $correlationId,
                'actorType' => 'service', 'actorId' => 'payment-service',
            ));
            if (empty($created['ok'])) { return $created; }
            $existing = $this->record($created['admissionId']);
        }
        return $this->admit($existing['id'], $correlationId,
            array('type' => 'service', 'id' => 'payment-service'));
    }

    public function admit($admissionId, $correlationId, array $actor = array())
    {
        $row = $this->record($admissionId);
        $correlationId = $this->identifier($correlationId, 64);
        $actor = $this->actorDescriptor($actor['type'] ?? 'user', $actor['id'] ?? null);
        if ($row === null || $correlationId === null || $row['review_status'] === 'revoked') {
            return $this->result(false, 'invalid_admission');
        }
        if ($row['review_status'] === 'admitted') { return $this->result(true, 'already_admitted', $row['id']); }
        $groupId = $this->groups->groupIdForName($row['course_code'] . '^Students');
        $permissionUserId = $this->identity->permissionUserIdForUser($row['user_id']);
        if (!$groupId || $permissionUserId === null) { return $this->result(false, 'student_group_unavailable'); }
        $wasMember = $this->groups->isGroupMember($row['user_id'], $groupId);
        $this->beginTransaction();
        $grant = $this->entitlements->grant(array(
            'userId' => $row['user_id'], 'entitlementType' => 'resource_access',
            'resourceType' => 'course', 'resourceId' => $row['course_code'],
            'sourceType' => 'private_admission', 'sourceReference' => $row['id'],
            'idempotencyKey' => 'private-admission:' . $row['id'],
            'correlationId' => $correlationId,
            'metadata' => array('admission_id' => $row['id'], 'payment_status' => $row['payment_status']),
            'effectiveAt' => date('Y-m-d H:i:s'), 'expiresAt' => null,
            'grantedByType' => $actor['type'], 'grantedById' => $actor['id'],
        ), false);
        if (empty($grant['ok']) || (!$wasMember && !$this->groups->ensureMembership($groupId, $permissionUserId))) {
            $this->rollbackTransaction();
            return $this->result(false, 'admission_grant_failed');
        }
        $now = date('Y-m-d H:i:s');
        $fields = array('review_status' => 'admitted', 'entitlement_grant_id' => $grant['grantId'],
            'student_membership_added' => $wasMember ? 0 : 1, 'admitted_by' => $actor['id'],
            'admitted_at' => $now, 'updated_at' => $now, 'correlation_id' => $correlationId);
        $eventRow = array_merge($row, $fields);
        if ($this->update('id', $row['id'], $fields) === false
            || !$this->audit('private_admission.admitted', $eventRow, 'succeeded', $actor)) {
            $this->rollbackTransaction();
            return $this->result(false, 'admission_grant_failed');
        }
        $this->commitTransaction();
        return $this->result(true, 'admitted', $row['id']);
    }

    public function updateReview($admissionId, array $input)
    {
        $row = $this->record($admissionId);
        $status = $this->enum($input['reviewStatus'] ?? '', self::REVIEW);
        $payment = $this->enum($input['paymentStatus'] ?? '', self::PAYMENT);
        $correlation = $this->identifier($input['correlationId'] ?? '', 64);
        $reason = $this->text($input['reason'] ?? '', 4000);
        $reference = $this->text($input['paymentReference'] ?? '', 191, true);
        if ($row === null || in_array($row['review_status'], array('admitted', 'revoked'), true)
            || $status === null || $payment === null || $correlation === null || $reason === null) {
            return $this->result(false, 'invalid_admission_update');
        }
        $fields = array('review_status' => $status, 'payment_status' => $payment,
            'payment_reference' => $reference, 'reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'), 'correlation_id' => $correlation);
        $this->beginTransaction();
        if ($this->update('id', $row['id'], $fields) === false
            || !$this->audit('private_admission.review_updated', array_merge($row, $fields), 'succeeded')) {
            $this->rollbackTransaction(); return $this->result(false, 'admission_update_failed');
        }
        $this->commitTransaction(); return $this->result(true, 'admission_updated', $row['id']);
    }

    public function revoke($admissionId, $reason, $correlationId, array $actor = array())
    {
        $row = $this->record($admissionId);
        $correlationId = $this->identifier($correlationId, 64);
        $reason = $this->text($reason, 191);
        if ($row === null || $row['review_status'] !== 'admitted' || $correlationId === null || $reason === null) {
            return $this->result(false, 'invalid_revocation');
        }
        $groupId = $this->groups->groupIdForName($row['course_code'] . '^Students');
        $permissionUserId = $this->identity->permissionUserIdForUser($row['user_id']);
        $actor=$this->actorDescriptor($actor['type']??'user',$actor['id']??null);
        $this->beginTransaction();
        $revoked = $this->entitlements->revoke($row['entitlement_grant_id'], 'private_admission_revoked',
            $actor, $correlationId, false);
        if (empty($revoked['ok']) || ((int) $row['student_membership_added'] === 1
            && (!$groupId || $permissionUserId === null || !$this->groups->removeMembership($groupId, $permissionUserId)))) {
            $this->rollbackTransaction();
            return $this->result(false, 'admission_revocation_failed');
        }
        $now = date('Y-m-d H:i:s');
        $fields = array('review_status' => 'revoked', 'reason' => $reason,
            'revoked_by' => $actor['id'], 'revoked_at' => $now,
            'updated_at' => $now, 'correlation_id' => $correlationId);
        if ($this->update('id', $row['id'], $fields) === false
            || !$this->audit('private_admission.revoked', array_merge($row, $fields), 'succeeded')) {
            $this->rollbackTransaction(); return $this->result(false, 'admission_revocation_failed');
        }
        $this->commitTransaction(); return $this->result(true, 'admission_revoked', $row['id']);
    }

    public function revokeConfirmedPayment($courseCode,$userId,$paymentReference,$correlationId)
    {
        $row=$this->latest($courseCode,$userId);
        if($row===null||$row['review_status']!=='admitted'||(string)$row['payment_reference']!==(string)$paymentReference) return $this->result(false,'paid_admission_not_found');
        return $this->revoke($row['id'],'Verified payment reversed',$correlationId,array('type'=>'service','id'=>'payment-service'));
    }

    public function listForCourse($courseCode, $limit = 250)
    {
        $course = $this->privateCourse($courseCode);
        if ($course === null) { return array(); }
        $rows = $this->getArray('SELECT * FROM ' . self::TABLE_NAME . ' WHERE course_code = '
            . $this->quote($course['contextcode']) . ' ORDER BY updated_at DESC, id DESC LIMIT ' . min(500, max(1, (int) $limit)));
        return is_array($rows) ? $rows : array();
    }

    public function isAdmitted($courseCode,$userId)
    {
        $row=$this->latest($courseCode,$userId);
        return is_array($row)&&$row['review_status']==='admitted';
    }

    public function record($id) { $id = $this->hex($id); return $id === null ? null : $this->one('id', $id); }
    private function latest($course, $user) { $r=$this->getArray('SELECT * FROM '.self::TABLE_NAME.' WHERE course_code='.$this->quote($course).' AND user_id='.$this->quote($user).' ORDER BY created_at DESC,id DESC LIMIT 1'); return is_array($r)&&count($r)?$r[0]:null; }
    private function privateCourse($code) { $code=$this->text($code,255); $c=$code===null?false:$this->contexts->getContext($code); return is_array($c)&&strtolower((string)($c['access_policy']??''))==='private'?$c:null; }
    private function user($id) { $id=$this->text($id,25); return $id===null?null:$this->users->findByUserId($id); }
    private function audit($type,array $row,$outcome,array $actor=array()) { $actor=$this->actorDescriptor($actor['type']??'user',$actor['id']??null);$e=$this->events->append(array('eventType'=>$type,'subjectType'=>'user','subjectId'=>$row['user_id'],'actorType'=>$actor['type'],'actorId'=>$actor['id'],'outcome'=>$outcome,'correlationId'=>$row['correlation_id'],'sourceService'=>'membership-service','metadata'=>array('admission_id'=>$row['id'],'course_code'=>$row['course_code'],'review_status'=>$row['review_status'],'payment_status'=>$row['payment_status']))); return !empty($e['ok']); }
    private function actorDescriptor($type,$id) { $type=strtolower(trim((string)$type));if($type==='service'){return array('type'=>'service','id'=>$this->text($id,25)?:'payment-service');}return array('type'=>'user','id'=>(string)$this->actor->userId()); }
    private function one($field,$value) { $r=$this->getAll('WHERE '.$field.' = '.$this->quote($value).' LIMIT 1'); return is_array($r)&&count($r)?$r[0]:null; }
    private function enum($v,array $a) { $v=strtolower(trim((string)$v)); return in_array($v,$a,true)?$v:null; }
    private function identifier($v,$m) { $v=trim((string)$v); return $v!==''&&strlen($v)<=$m&&preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/',$v)?$v:null; }
    private function hex($v) { $v=strtolower(trim((string)$v)); return preg_match('/^[a-f0-9]{32}$/',$v)?$v:null; }
    private function text($v,$m,$empty=false) { if(!is_scalar($v))return null;$v=trim((string)$v);if($v==='')return $empty?'':null;return strlen($v)<=$m&&!preg_match('/[\x00-\x1F\x7F]/',$v)?$v:null; }
    private function quote($v) { $d=$this->objEngine->getDbObj();return method_exists($d,'quoteSmart')?$d->quoteSmart((string)$v):"'".str_replace("'","''",(string)$v)."'"; }
    private function result($ok,$code,$id=null) { return array('ok'=>(bool)$ok,'code'=>$code,'admissionId'=>$id); }
}
?>
