<?php
/** Membership operations workbench. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }

class membership_service extends controller
{
    private const CSRF = 'membership_service_role_admin';

    public function init()
    {
        $this->setLayoutTemplate('membership_layout.php');
        $this->authorization = $this->getObject(
            'membershipauthorizationservice',
            'membership-service'
        );
        $this->memberships = $this->getObject(
            'membershipservice',
            'membership-service'
        );
        $this->admissions = $this->getObject('privateadmissionservice', 'membership-service');
        $this->contexts = $this->getObject('dbcontext', 'context');
        $this->users = $this->getObject('userservice', 'security');
        $this->user = $this->getObject('user', 'security');
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $this->csrf = $stack['csrf'];
    }

    public function requiresLogin($action) { return true; }

    public function dispatch($action)
    {
        switch ((string) $action) {
            case 'assignrole': return $this->assignRole();
            case 'removerole': return $this->removeRole();
            case 'createperiod': return $this->createPeriod();
            case 'editperiod': return $this->editPeriod();
            case 'transition': return $this->transitionPeriod();
            case 'admissions': return $this->admissionsHome();
            case 'createadmission': return $this->createAdmission();
            case 'updateadmission': return $this->updateAdmission();
            case 'admit': return $this->admit();
            case 'revokeadmission': return $this->revokeAdmission();
            case 'previewcsv': return $this->previewCsv();
            case 'importcsv': return $this->importCsv();
            default: return $this->home();
        }
    }

    private function admissionsHome($message = '', $error = '', array $preview = array())
    {
        if (!$this->authorization->can('private_admission.manage')) {
            return $this->nextAction(null, array('error' => 'noaccess'), '_default');
        }
        $courseCode = $this->param('contextcode');
        $course = $this->contexts->getContext($courseCode);
        if (!is_array($course) || strtolower((string) ($course['access_policy'] ?? '')) !== 'private') {
            return $this->home('', 'private_course_required');
        }
        $rows = $this->admissions->listForCourse($courseCode);
        foreach ($rows as &$row) {
            $person = $this->users->findByUserId($row['user_id']);
            $row['person'] = is_array($person) ? trim($person['firstname'].' '.$person['surname']) : $row['user_id'];
            $row['username'] = is_array($person) ? $person['username'] : '';
        }
        unset($row);
        $this->setVar('admissionCourse', $course);
        $this->setVar('admissionRows', $rows);
        $this->setVar('admissionUsers', $this->users->listUsers('', false));
        $this->setVar('admissionPreview', $preview);
        $this->setVar('admissionCsrf', $this->csrf->issue(self::CSRF));
        $this->setVar('admissionMessage', $message);
        $this->setVar('admissionError', $error);
        return 'admissions_tpl.php';
    }

    private function createAdmission()
    {
        if (!$this->validPost() || !$this->authorization->can('private_admission.manage')) {
            return $this->admissionsHome('', 'invalid_request');
        }
        $result = $this->admissions->createReview(array(
            'courseCode'=>$this->param('contextcode'), 'userId'=>$this->param('user_id'),
            'reviewStatus'=>$this->param('review_status'), 'paymentStatus'=>$this->param('payment_status'),
            'paymentReference'=>$this->param('payment_reference'), 'reason'=>$this->param('reason'),
            'correlationId'=>$this->correlationId(),
        ));
        return $this->admissionsHome(!empty($result['ok'])?'admission_recorded':'', empty($result['ok'])?$result['code']:'');
    }

    private function admit()
    {
        if (!$this->validPost() || !$this->authorization->can('private_admission.manage')) {
            return $this->admissionsHome('', 'invalid_request');
        }
        $result=$this->admissions->admit($this->param('admission_id'),$this->correlationId());
        return $this->admissionsHome(!empty($result['ok'])?'learner_admitted':'',empty($result['ok'])?$result['code']:'');
    }

    private function updateAdmission()
    {
        if (!$this->validPost() || !$this->authorization->can('private_admission.manage')) {
            return $this->admissionsHome('', 'invalid_request');
        }
        $result=$this->admissions->updateReview($this->param('admission_id'),array(
            'reviewStatus'=>$this->param('review_status'), 'paymentStatus'=>$this->param('payment_status'),
            'paymentReference'=>$this->param('payment_reference'), 'reason'=>$this->param('reason'),
            'correlationId'=>$this->correlationId(),
        ));
        return $this->admissionsHome(!empty($result['ok'])?'admission_updated':'',empty($result['ok'])?$result['code']:'');
    }

    private function revokeAdmission()
    {
        if (!$this->validPost() || !$this->authorization->can('private_admission.manage')) {
            return $this->admissionsHome('', 'invalid_request');
        }
        $result=$this->admissions->revoke($this->param('admission_id'),$this->param('reason'),$this->correlationId());
        return $this->admissionsHome(!empty($result['ok'])?'admission_revoked':'',empty($result['ok'])?$result['code']:'');
    }

    private function previewCsv()
    {
        if (!$this->validPost() || !$this->authorization->can('private_admission.manage')) {
            return $this->admissionsHome('', 'invalid_request');
        }
        $courseCode=$this->param('contextcode');
        $file=$_FILES['admission_csv']??array();
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $this->admissionsHome('', 'csv_required');
        }
        $handle=fopen($file['tmp_name'],'rb'); $header=$handle?fgetcsv($handle):false;
        $expected=array('username','email','status','payment_reference','reason');
        if ($header===false || array_map('strtolower',array_map('trim',$header))!==$expected) {
            if (is_resource($handle)) fclose($handle);
            return $this->admissionsHome('', 'invalid_csv_headers');
        }
        $preview=array(); $line=1;
        while (($values=fgetcsv($handle))!==false && count($preview)<500) {
            $line++; $values=array_pad($values,5,''); $row=array_combine($expected,array_slice($values,0,5));
            $byUsername=trim($row['username'])!==''?$this->users->findByUsername($row['username']):null;
            $byEmail=trim($row['email'])!==''?$this->users->findByEmail($row['email']):null;
            $person=$byUsername?:$byEmail;
            $row['status']=strtolower(trim((string)$row['status']));
            $validStatuses=array('awaiting_payment','payment_under_review','ready_for_admission');
            $row['line']=$line; $row['user_id']=is_array($person)?$person['userid']:'';
            $identityMatches=is_array($person)&&(!$byUsername||!$byEmail||(string)$byUsername['userid']===(string)$byEmail['userid']);
            $statusValid=in_array($row['status'],$validStatuses,true);
            $row['valid']=$identityMatches&&$statusValid;
            $row['error']=$identityMatches
                ? ($statusValid?'':'Unknown admission status.')
                : 'No single existing user matches this username and email.';
            $preview[]=$row;
        }
        fclose($handle);
        $token=bin2hex(random_bytes(16));
        $this->setSession('admissionCsvPreview',array('token'=>$token,'course'=>$courseCode,'rows'=>$preview));
        foreach ($preview as &$row) { $row['token']=$token; } unset($row);
        return $this->admissionsHome('', '', $preview);
    }

    private function importCsv()
    {
        if (!$this->validPost() || !$this->authorization->can('private_admission.manage')) {
            return $this->admissionsHome('', 'invalid_request');
        }
        $saved=$this->getSession('admissionCsvPreview');
        if (!is_array($saved)||!hash_equals((string)($saved['token']??''),$this->param('preview_token'))
            || (string)($saved['course']??'')!==$this->param('contextcode')) {
            return $this->admissionsHome('', 'csv_preview_expired');
        }
        $created=0;
        foreach ($saved['rows'] as $row) {
            if (empty($row['valid'])) continue;
            $result=$this->admissions->createReview(array('courseCode'=>$saved['course'],'userId'=>$row['user_id'],
                'reviewStatus'=>in_array($row['status'],array('awaiting_payment','payment_under_review','ready_for_admission'),true)?$row['status']:'awaiting_payment',
                'paymentStatus'=>trim($row['payment_reference'])!==''?'paid':'not_recorded',
                'paymentReference'=>$row['payment_reference'],'reason'=>$row['reason'],'correlationId'=>$this->correlationId()));
            if (!empty($result['ok'])) $created++;
        }
        $this->unsetSession('admissionCsvPreview');
        return $this->admissionsHome(
            $created . ($created === 1
                ? ' admission record imported' : ' admission records imported'),
            ''
        );
    }

    private function home($message = '', $error = '')
    {
        if (!$this->authorization->can('membership.view')) {
            return $this->nextAction(null, array('error' => 'noaccess'), '_default');
        }
        if ($this->user->isAdmin()) {
            $this->authorization->ensureDefaultRole();
        }
        $periods = $this->memberships->listPeriods(200);
        foreach ($periods as &$period) {
            $record = $this->users->findByUserId($period['user_id']);
            $period['person'] = is_array($record)
                ? trim($record['firstname'] . ' ' . $record['surname'])
                : $period['user_id'];
            $period['username'] = is_array($record) ? $record['username'] : '';
        }
        unset($period);
        $this->setVar('membershipPeriods', $periods);
        $editPeriod = $this->memberships->getPeriod($this->param('edit'));
        $this->setVar('membershipEditPeriod', $editPeriod);
        $this->setVar('membershipUsers', $this->users->listUsers('', false));
        $this->setVar('membershipIsAdmin', $this->user->isAdmin());
        $this->setVar('membershipCanManage',
            $this->authorization->can('membership.manage'));
        $this->setVar('membershipCanOverride',
            $this->authorization->can('membership.override'));
        $this->setVar('membershipRoleMembers',
            $this->authorization->defaultRoleMembers());
        $this->setVar('membershipRoleCandidates',
            $this->authorization->availableDefaultRoleUsers());
        $this->setVar('membershipCapabilities',
            $this->authorization->capabilities());
        $this->setVar('membershipDefaultCapabilities',
            $this->authorization->defaultRoleCapabilities());
        $this->setVar('membershipCsrf', $this->csrf->issue(self::CSRF));
        $this->setVar('membershipMessage', $message);
        $this->setVar('membershipError', $error);
        return 'manage_tpl.php';
    }

    private function assignRole()
    {
        if (!$this->validPost() || !$this->user->isAdmin()) {
            return $this->home('', 'invalid_request');
        }
        $result = $this->authorization->assignDefaultRole(
            $this->param('user_id'),
            $this->correlationId()
        );
        return $this->home(!empty($result['ok']) ? 'role_assigned' : '',
            empty($result['ok']) ? $result['code'] : '');
    }

    private function removeRole()
    {
        if (!$this->validPost() || !$this->user->isAdmin()) {
            return $this->home('', 'invalid_request');
        }
        $result = $this->authorization->removeDefaultRole(
            $this->param('user_id'),
            $this->correlationId()
        );
        return $this->home(!empty($result['ok']) ? 'role_removed' : '',
            empty($result['ok']) ? $result['code'] : '');
    }

    private function createPeriod()
    {
        if (!$this->validPost()
            || !$this->authorization->can('membership.manage')
            || !$this->authorization->can('membership.override')) {
            return $this->home('', 'invalid_request');
        }
        $startsAt = $this->dateBoundary($this->param('starts_at'), false);
        $endsAt = $this->dateBoundary($this->param('ends_at'), true);
        $reason = $this->param('reason');
        if ($reason === '') {
            return $this->home('', 'reason_required');
        }
        $correlationId = $this->correlationId();
        $result = $this->memberships->createPeriod(array(
            'userId' => $this->param('user_id'),
            'tier' => $this->param('tier'),
            'state' => $startsAt !== null
                && $startsAt > date('Y-m-d H:i:s') ? 'scheduled' : 'active',
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
            'sourceType' => 'manual_override',
            'sourceReference' => $reason,
            'idempotencyKey' => $correlationId,
            'correlationId' => $correlationId,
        ));
        return $this->home(!empty($result['ok']) ? 'membership_created' : '',
            empty($result['ok']) ? $result['code'] : '');
    }

    private function transitionPeriod()
    {
        if (!$this->validPost()
            || !$this->authorization->can('membership.manage')) {
            return $this->home('', 'invalid_request');
        }
        $nextState = $this->param('next_state');
        $graceEndsAt = $nextState === 'grace'
            ? $this->dateBoundary($this->param('grace_ends_at'), true) : null;
        $result = $this->memberships->transition(
            $this->param('period_id'),
            $nextState,
            $this->correlationId(),
            $graceEndsAt
        );
        return $this->home(!empty($result['ok']) ? 'membership_updated' : '',
            empty($result['ok']) ? $result['code'] : '');
    }

    private function editPeriod()
    {
        if (!$this->validPost()
            || !$this->authorization->can('membership.manage')
            || !$this->authorization->can('membership.override')) {
            return $this->home('', 'invalid_request');
        }
        $reason = $this->param('reason');
        if ($reason === '') {
            return $this->home('', 'reason_required');
        }
        $correlationId = $this->correlationId();
        $result = $this->memberships->amendPeriod(
            $this->param('period_id'),
            array(
                'tier' => $this->param('tier'),
                'startsAt' => $this->dateBoundary($this->param('starts_at'), false),
                'endsAt' => $this->dateBoundary($this->param('ends_at'), true),
                'sourceType' => 'manual_override',
                'sourceReference' => $reason,
                'idempotencyKey' => 'membership-amendment:' . $correlationId,
                'correlationId' => $correlationId,
            )
        );
        return $this->home(!empty($result['ok']) ? 'membership_amended' : '',
            empty($result['ok']) ? $result['code'] : '');
    }

    private function validPost()
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST'
            && $this->csrf->consume(self::CSRF, $this->param('csrf_token'));
    }

    private function correlationId()
    {
        return 'membership-role:' . date('YmdHis') . ':'
            . bin2hex(random_bytes(6));
    }

    private function dateBoundary($value, $endOfDay)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }
        return $date->format('Y-m-d') . ($endOfDay ? ' 23:59:59' : ' 00:00:00');
    }

    private function param($name)
    {
        $value = $this->getParam($name, '');
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
class_alias('membership_service', 'membership-service');
?>
