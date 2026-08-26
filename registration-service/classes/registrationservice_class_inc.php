<?php
/**
 * Public-registration application boundary.
 *
 * This service owns pending workflow state only. Canonical users are not
 * created until a later, explicitly verified provisioning operation.
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class registrationservice extends dbTable
{
    private const TABLE_NAME = 'tbl_registration_service_pending';
    private const PENDING_TTL = 172800;
    private const VERIFICATION_TTL = 86400;

    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorHandler'
    ) {
        parent::init(
            $tableName !== null ? $tableName : self::TABLE_NAME,
            $pearDb,
            $errorCallback
        );
        $this->objUsers = $this->getObject('userservice', 'security');
        $this->objAuthentication = $this->getObject(
            'authenticationservice',
            'security'
        );
        $this->objTokens = $this->getObject(
            'registrationtokenservice',
            'registration-service'
        );
        $this->objLegal = $this->getObject(
            'legalacceptanceservice',
            'legal-acceptance-service'
        );
        $this->objCommunications = $this->getObject(
            'communicationservice',
            'communications'
        );
        $this->objEvents = $this->getObject(
            'accounteventservice',
            'account-event-service'
        );
        $this->objCredentials = $this->getObject(
            'accountcredentialservice',
            'security'
        );
        $this->objConfig = $this->getObject('altconfig', 'config');
        $this->objPhone = $this->getObject('internationalphonenumber', 'registration-service');
    }

    /** Create pending state only; no canonical account is created or activated. */
    public function createPending(array $request)
    {
        $username = $this->username($request['username'] ?? null);
        $email = $this->email($request['emailAddress'] ?? null);
        $firstName = $this->text($request['firstName'] ?? null, 50);
        $surname = $this->text($request['surname'] ?? null, 50);
        $mobileNumber = $this->objPhone->normalize(
            $request['countryCallingCode'] ?? null,
            $request['mobileNumber'] ?? null
        );
        $correlationId = $this->identifier($request['correlationId'] ?? null, 64);
        $password = $request['password'] ?? null;
        if ($mobileNumber === null) {
            return $this->result(false, 'invalid_mobile_number');
        }
        if ($username === null || $email === null || $firstName === null
            || $surname === null || $correlationId === null
            || !is_scalar($password) || trim((string) $password) === '') {
            return $this->result(false, 'invalid_registration');
        }
        if (!$this->objUsers->usernameAvailable($username)) {
            return $this->result(false, 'username_taken');
        }
        if (!$this->objUsers->emailAvailable($email)) {
            return $this->result(false, 'email_taken');
        }
        $existingEmail = $this->livePending('email_address', $email);
        if ($existingEmail !== null) {
            return $this->result(false, 'registration_already_pending', $existingEmail['id']);
        }
        if ($this->hasLivePending('username', $username)) {
            return $this->result(false, 'username_taken');
        }
        try {
            $passwordHash = $this->objAuthentication->createPasswordHash(
                (string) $password
            );
        } catch (Exception $exception) {
            return $this->result(false, 'invalid_password');
        }

        $id = bin2hex(random_bytes(16));
        $now = date('Y-m-d H:i:s');
        $this->beginTransaction();
        if ($this->insert(array(
            'id' => $id,
            'username' => $username,
            'email_address' => $email,
            'first_name' => $firstName,
            'surname' => $surname,
            'mobile_number' => $mobileNumber,
            'password_hash' => $passwordHash,
            'status' => 'awaiting_legal_acceptance',
            'correlation_id' => $correlationId,
            'expires_at' => date('Y-m-d H:i:s', time() + self::PENDING_TTL),
            'verified_at' => null,
            'provisioned_user_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        )) === false || !$this->appendEvent(
            'registration.pending.created',
            $id,
            $correlationId,
            'requested'
        )) {
            $this->rollbackTransaction();
            return $this->result(false, 'pending_registration_failed');
        }
        $this->commitTransaction();
        return $this->result(true, 'pending_registration_created', $id);
    }

    /**
     * Check a proposed username and return a small, bounded set of alternatives.
     * No user records or other account attributes are exposed.
     */
    public function usernameAvailability($requested, $firstName = '', $surname = '')
    {
        $username = $this->username($requested);
        if ($username === null) {
            return array('valid' => false, 'available' => false, 'suggestions' => array());
        }
        if ($this->isUsernameAvailable($username)) {
            return array('valid' => true, 'available' => true, 'suggestions' => array());
        }

        $first = $this->usernamePart($firstName);
        $last = $this->usernamePart($surname);
        $candidates = array();
        if ($first !== '' && $last !== '') {
            $candidates = array(
                $first . substr($last, 0, 1),
                $first . '.' . $last,
                $first . $last,
                substr($first, 0, 1) . $last,
            );
        }
        $candidates[] = $username . '1';
        $candidates[] = $username . '2';

        $suggestions = array();
        foreach (array_values(array_unique($candidates)) as $candidate) {
            if (count($suggestions) >= 3) {
                break;
            }
            if ($candidate !== $username && $this->username($candidate) !== null
                && $this->isUsernameAvailable($candidate)) {
                $suggestions[] = $candidate;
            }
        }
        return array('valid' => true, 'available' => false, 'suggestions' => $suggestions);
    }

    /** Record legal evidence, issue a token, and queue its verification email. */
    public function acceptLegalAndQueueVerification(
        $pendingId,
        array $policy,
        array $evidence = array()
    ) {
        $pending = $this->pending($pendingId, 'awaiting_legal_acceptance');
        if ($pending === null) {
            return $this->result(false, 'pending_registration_not_ready');
        }
        $acceptance = $this->objLegal->recordAcceptance(array(
            'subjectType' => 'pending_registration',
            'subjectId' => $pending['id'],
            'policyKey' => $policy['policyKey'] ?? null,
            'policyVersion' => $policy['policyVersion'] ?? null,
            'contentDigest' => $policy['contentDigest'] ?? null,
            'acceptanceMethod' => $evidence['acceptanceMethod'] ?? 'checkbox',
            'ipAddress' => $evidence['ipAddress'] ?? '',
            'userAgent' => $evidence['userAgent'] ?? '',
            'locale' => $evidence['locale'] ?? '',
            'correlationId' => $pending['correlation_id'],
            'acceptedAt' => $evidence['acceptedAt'] ?? null,
        ));
        if (empty($acceptance['ok'])) {
            return $this->result(false, 'legal_acceptance_failed', $pending['id']);
        }
        if (!$this->objLegal->hasAccepted(
            'pending_registration',
            $pending['id'],
            $policy['policyKey'] ?? null,
            $policy['policyVersion'] ?? null,
            $policy['contentDigest'] ?? null
        )) {
            return $this->result(false, 'legal_acceptance_not_confirmed', $pending['id']);
        }

        return $this->prepareAndQueueVerification($pending);
    }

    /** Resume a saved registration rather than asking the person to start over. */
    public function resumeVerification($pendingId, array $policy)
    {
        $pending = $this->pendingWithStatuses(
            $pendingId,
            array('awaiting_legal_acceptance', 'awaiting_verification')
        );
        if ($pending === null || !$this->objLegal->hasAccepted(
            'pending_registration',
            $pending['id'],
            $policy['policyKey'] ?? null,
            $policy['policyVersion'] ?? null,
            $policy['contentDigest'] ?? null
        )) {
            return $this->result(false, 'pending_registration_not_ready', $pendingId);
        }
        return $this->prepareAndQueueVerification($pending);
    }

    private function prepareAndQueueVerification(array $pending)
    {
        if ($pending['status'] !== 'awaiting_verification') {
            if ($this->update('id', $pending['id'], array(
                'status' => 'awaiting_verification',
                'updated_at' => date('Y-m-d H:i:s'),
            )) === false) {
                return $this->result(false, 'verification_state_failed', $pending['id']);
            }
            $pending['status'] = 'awaiting_verification';
        }

        $token = $this->objTokens->issue(
            'email_verification',
            'pending_registration',
            $pending['id'],
            $pending['correlation_id'],
            self::VERIFICATION_TTL
        );
        if (empty($token['ok']) || empty($token['rawToken'])) {
            return $this->result(false, 'verification_token_failed', $pending['id']);
        }
        $url = rtrim($this->objConfig->getSiteRoot(), '/')
            . '/index.php?module=registration-service&action=verify&token='
            . rawurlencode($token['rawToken']);
        $siteName = $this->siteName();
        $email = $this->actionEmail(
            $pending['first_name'],
            'Verify your email address',
            'Thanks for creating a ' . $siteName . ' account. Confirm that this email address belongs to you to finish setting up your account.',
            'Verify email address',
            $url,
            'This verification link expires in 24 hours.',
            'If you did not create this account, you can safely ignore this email.'
        );
        $message = $this->objCommunications->queueEmail(array(
            'to' => $pending['email_address'],
            'toName' => $pending['first_name'] . ' ' . $pending['surname'],
            'subject' => 'Verify your email address | ' . $siteName,
            'text' => $email['text'],
            'html' => $email['html'],
            'idempotencyKey' => 'registration-verification:' . $token['tokenId'],
            'metadata' => array(
                'purpose' => 'registration_verification',
                'pendingRegistrationId' => $pending['id'],
            ),
        ));
        if (empty($message['ok'])) {
            return $this->result(false, 'verification_email_failed', $pending['id']);
        }
        // Audit failure must not turn a successfully queued email into a false
        // form failure. The canonical pending state is already resumable.
        $this->appendEvent(
            'registration.verification.requested',
            $pending['id'],
            $pending['correlation_id'],
            'requested'
        );
        return array(
            'ok' => true,
            'code' => 'verification_queued',
            'pendingId' => $pending['id'],
            'username' => $pending['username'],
            'emailAddress' => $pending['email_address'],
        );
    }

    /** Consume the token and mark pending state verified atomically. */
    public function completeEmailVerification($rawToken)
    {
        $pending = null;
        $result = $this->objTokens->consumeWith(
            'email_verification',
            $rawToken,
            function ($subjectType, $subjectId) use (&$pending) {
                if ($subjectType !== 'pending_registration') {
                    return false;
                }
                $pending = $this->pending($subjectId, 'awaiting_verification');
                if ($pending === null) {
                    return false;
                }
                $now = date('Y-m-d H:i:s');
                if ($this->update('id', $subjectId, array(
                    'status' => 'verified',
                    'verified_at' => $now,
                    'updated_at' => $now,
                )) === false) {
                    return false;
                }
                return $this->appendEvent(
                    'registration.email.verified',
                    $pending['id'],
                    $pending['correlation_id'],
                    'succeeded'
                );
            }
        );
        if (empty($result['ok']) || $pending === null) {
            return $this->result(false, $result['code'] ?? 'verification_failed');
        }
        return $this->result(true, 'email_verified', $pending['id']);
    }

    /** Provision one verified request into the canonical identity services. */
    public function provisionVerified($pendingId)
    {
        $pendingId = is_scalar($pendingId)
            ? strtolower(trim((string) $pendingId)) : '';
        if (!preg_match('/^[a-f0-9]{32}$/', $pendingId)) {
            return $this->result(false, 'invalid_pending_registration');
        }
        $this->beginTransaction();
        $rows = $this->getArray(
            'SELECT * FROM ' . self::TABLE_NAME
            . ' WHERE id = ' . $this->quote($pendingId)
            . " AND status = 'verified'"
            . ' AND expires_at > ' . $this->quote(date('Y-m-d H:i:s'))
            . ' LIMIT 2 FOR UPDATE'
        );
        if (!is_array($rows) || count($rows) !== 1) {
            $this->rollbackTransaction();
            return $this->result(false, 'pending_registration_not_verified');
        }
        $pending = $rows[0];
        if (!$this->objUsers->usernameAvailable($pending['username'])
            || !$this->objUsers->emailAvailable($pending['email_address'])) {
            $this->rollbackTransaction();
            return $this->result(false, 'canonical_identity_conflict', $pendingId);
        }
        $userId = $this->objUsers->generateUserId();
        if ($userId === null) {
            $this->rollbackTransaction();
            return $this->result(false, 'userid_allocation_failed', $pendingId);
        }
        $provisioning = $this->getObject(
            'userprovisioningservice',
            'security'
        );
        $created = $provisioning->createLocalUserWithPasswordHash(array(
            'userId' => $userId,
            'username' => $pending['username'],
            'firstName' => $pending['first_name'],
            'surname' => $pending['surname'],
            'emailAddress' => $pending['email_address'],
            'title' => '',
            'country' => '',
            // Pending registrations created before version 1.004 have no mobile value.
            'cellnumber' => (string) ($pending['mobile_number'] ?? ''),
            'staffnumber' => '',
            'sex' => '',
            'isActive' => true,
            'howCreated' => 'registration-service',
        ), $pending['password_hash']);
        if (empty($created['ok']) || empty($created['userId'])) {
            $this->rollbackTransaction();
            return $this->result(
                false,
                $created['code'] ?? 'canonical_provisioning_failed',
                $pendingId
            );
        }
        $now = date('Y-m-d H:i:s');
        if ($this->update('id', $pendingId, array(
            'status' => 'provisioned',
            'provisioned_user_id' => $created['userId'],
            'password_hash' => null,
            'updated_at' => $now,
        )) === false || !$this->appendEvent(
            'registration.account.provisioned',
            $pendingId,
            $pending['correlation_id'],
            'succeeded'
        )) {
            $this->rollbackTransaction();
            return $this->result(false, 'provisioning_finalization_failed', $pendingId);
        }
        $userEvent = $this->objEvents->append(array(
            'eventType' => 'account.registration.completed',
            'subjectType' => 'user',
            'subjectId' => $created['userId'],
            'actorType' => 'system',
            'actorId' => 'registration-service',
            'outcome' => 'succeeded',
            'correlationId' => $pending['correlation_id'],
            'sourceService' => 'registration-service',
            'metadata' => array('pending_registration_id' => $pendingId),
        ));
        if (empty($userEvent['ok'])) {
            $this->rollbackTransaction();
            return $this->result(false, 'provisioning_audit_failed', $pendingId);
        }
        $this->commitTransaction();
        return array(
            'ok' => true,
            'code' => 'account_provisioned',
            'pendingId' => $pendingId,
            'userId' => $created['userId'],
        );
    }

    /**
     * Request recovery without disclosing whether the address has an account.
     */
    public function requestPasswordRecovery($emailAddress, $correlationId)
    {
        $emailAddress = $this->email($emailAddress);
        $correlationId = $this->identifier($correlationId, 64);
        if ($emailAddress === null || $correlationId === null) {
            return array('ok' => true, 'code' => 'recovery_request_received');
        }
        $user = $this->objUsers->findByEmail($emailAddress);
        if (!is_array($user) || empty($user['userid'])
            || empty($user['isactive'])) {
            return array('ok' => true, 'code' => 'recovery_request_received');
        }
        $token = $this->objTokens->issue(
            'password_recovery',
            'user',
            $user['userid'],
            $correlationId,
            3600
        );
        if (!empty($token['ok']) && !empty($token['rawToken'])) {
            $url = rtrim($this->objConfig->getSiteRoot(), '/')
                . '/index.php?module=registration-service&action=recover&token='
                . rawurlencode($token['rawToken']);
            $siteName = $this->siteName();
            $email = $this->actionEmail(
                $user['firstname'],
                'Reset your password',
                'We received a request to reset the password for your ' . $siteName . ' account.',
                'Reset password',
                $url,
                'This password reset link expires in one hour.',
                'If you did not request a password reset, you can safely ignore this email. Your password will not change.'
            );
            $message = $this->objCommunications->queueEmail(array(
                'to' => $user['emailaddress'],
                'toName' => trim($user['firstname'] . ' ' . $user['surname']),
                'subject' => 'Reset your password | ' . $siteName,
                'text' => $email['text'],
                'html' => $email['html'],
                'idempotencyKey' => 'password-recovery:' . $token['tokenId'],
                'metadata' => array(
                    'purpose' => 'password_recovery',
                    'userId' => $user['userid'],
                ),
            ));
            $this->objEvents->append(array(
                'eventType' => 'account.password.recovery.requested',
                'subjectType' => 'user',
                'subjectId' => $user['userid'],
                'actorType' => 'anonymous',
                'actorId' => '',
                'outcome' => empty($message['ok']) ? 'failed' : 'requested',
                'reasonCode' => empty($message['ok'])
                    ? 'communication_queue_failed' : '',
                'correlationId' => $correlationId,
                'sourceService' => 'registration-service',
                'metadata' => array(),
            ));
        }
        return array('ok' => true, 'code' => 'recovery_request_received');
    }

    /** Atomically consume recovery proof, replace password, and revoke sessions. */
    public function completePasswordRecovery($rawToken, $newPassword, $correlationId)
    {
        $correlationId = $this->identifier($correlationId, 64);
        if ($correlationId === null || !is_scalar($newPassword)) {
            return array('ok' => false, 'code' => 'invalid_recovery_request');
        }
        $userId = null;
        $result = $this->objTokens->consumeWith(
            'password_recovery',
            $rawToken,
            function ($subjectType, $subjectId) use (
                &$userId,
                $newPassword,
                $correlationId
            ) {
                if ($subjectType !== 'user') {
                    return false;
                }
                $changed = $this->objCredentials->replaceWithinTransaction(
                    $subjectId,
                    (string) $newPassword
                );
                if (empty($changed['ok'])) {
                    return false;
                }
                $event = $this->objEvents->append(array(
                    'eventType' => 'account.password.recovery.completed',
                    'subjectType' => 'user',
                    'subjectId' => $subjectId,
                    'actorType' => 'anonymous',
                    'actorId' => '',
                    'outcome' => 'succeeded',
                    'correlationId' => $correlationId,
                    'sourceService' => 'registration-service',
                    'metadata' => array(),
                ));
                if (empty($event['ok'])) {
                    return false;
                }
                $userId = $subjectId;
                return true;
            }
        );
        if (empty($result['ok']) || $userId === null) {
            return array(
                'ok' => false,
                'code' => $result['code'] ?? 'password_recovery_failed',
            );
        }
        return array('ok' => true, 'code' => 'password_recovered', 'userId' => $userId);
    }

    private function pending($id, $status)
    {
        $id = is_scalar($id) ? strtolower(trim((string) $id)) : '';
        if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
            return null;
        }
        $rows = $this->getArray(
            'SELECT * FROM ' . self::TABLE_NAME
            . ' WHERE id = ' . $this->quote($id)
            . ' AND status = ' . $this->quote($status)
            . ' AND expires_at > ' . $this->quote(date('Y-m-d H:i:s'))
            . ' LIMIT 2'
        );
        return is_array($rows) && count($rows) === 1 ? $rows[0] : null;
    }

    private function pendingWithStatuses($id, array $statuses)
    {
        $id = is_scalar($id) ? strtolower(trim((string) $id)) : '';
        $allowed = array('awaiting_legal_acceptance', 'awaiting_verification', 'verified');
        $statuses = array_values(array_intersect($allowed, $statuses));
        if (!preg_match('/^[a-f0-9]{32}$/', $id) || count($statuses) === 0) {
            return null;
        }
        $quoted = array_map(array($this, 'quote'), $statuses);
        $rows = $this->getArray(
            'SELECT * FROM ' . self::TABLE_NAME
            . ' WHERE id = ' . $this->quote($id)
            . ' AND status IN (' . implode(',', $quoted) . ')'
            . ' AND expires_at > ' . $this->quote(date('Y-m-d H:i:s'))
            . ' LIMIT 2'
        );
        return is_array($rows) && count($rows) === 1 ? $rows[0] : null;
    }

    private function hasLivePending($column, $value)
    {
        return $this->livePending($column, $value) !== null;
    }

    private function livePending($column, $value)
    {
        $allowed = array('username', 'email_address');
        if (!in_array($column, $allowed, true)) {
            return null;
        }
        $rows = $this->getArray(
            'SELECT id, username, email_address, status FROM ' . self::TABLE_NAME
            . ' WHERE ' . $column . ' = ' . $this->quote($value)
            . " AND status IN ('awaiting_legal_acceptance','awaiting_verification','verified')"
            . ' AND expires_at > ' . $this->quote(date('Y-m-d H:i:s'))
            . ' LIMIT 1'
        );
        return is_array($rows) && count($rows) === 1 ? $rows[0] : null;
    }

    private function isUsernameAvailable($username)
    {
        return $this->objUsers->usernameAvailable($username)
            && !$this->hasLivePending('username', $username);
    }

    private function usernamePart($value)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) {
                $value = $ascii;
            }
        }
        return preg_replace('/[^a-z0-9]+/', '', $value) ?: '';
    }

    private function appendEvent($type, $subjectId, $correlationId, $outcome)
    {
        $event = $this->objEvents->append(array(
            'eventType' => $type,
            'subjectType' => 'pending_registration',
            'subjectId' => $subjectId,
            'actorType' => 'anonymous',
            'actorId' => '',
            'outcome' => $outcome,
            'correlationId' => $correlationId,
            'sourceService' => 'registration-service',
            'metadata' => array(),
        ));
        return !empty($event['ok']);
    }

    private function actionEmail(
        $firstName,
        $heading,
        $introduction,
        $buttonLabel,
        $url,
        $expiry,
        $ignoreNotice
    ) {
        $siteName = $this->siteName();
        $firstName = trim((string) $firstName);
        $greeting = $firstName === '' ? 'Hello,' : 'Hello ' . $firstName . ',';
        $text = $greeting . "\n\n"
            . $introduction . "\n\n"
            . $buttonLabel . ":\n" . $url . "\n\n"
            . $expiry . "\n\n"
            . $ignoreNotice . "\n\n"
            . "Regards,\nThe " . $siteName . ' team';

        $escape = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
        $html = '<!doctype html><html><body style="margin:0;background:#f3f6f9;'
            . 'font-family:Arial,Helvetica,sans-serif;color:#172b4d">'
            . '<div style="display:none;max-height:0;overflow:hidden">'
            . $escape($introduction) . '</div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"'
            . ' style="background:#f3f6f9;padding:32px 12px"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"'
            . ' style="max-width:600px;background:#fff;border:1px solid #dfe3e8;'
            . 'border-radius:8px;overflow:hidden">'
            . '<tr><td style="background:#075985;color:#fff;padding:20px 32px;'
            . 'font-size:24px;font-weight:bold">' . $escape($siteName) . '</td></tr>'
            . '<tr><td style="padding:32px">'
            . '<p style="margin:0 0 20px;font-size:16px">' . $escape($greeting) . '</p>'
            . '<h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;color:#102a43">'
            . $escape($heading) . '</h1>'
            . '<p style="margin:0 0 24px;font-size:16px;line-height:1.6">'
            . $escape($introduction) . '</p>'
            . '<p style="margin:0 0 24px"><a href="' . $escape($url) . '"'
            . ' style="display:inline-block;background:#0879c1;color:#fff;text-decoration:none;'
            . 'font-size:16px;font-weight:bold;padding:13px 22px;border-radius:6px">'
            . $escape($buttonLabel) . '</a></p>'
            . '<p style="margin:0 0 20px;font-size:14px;line-height:1.5;color:#52667a">'
            . $escape($expiry) . '</p>'
            . '<p style="margin:0 0 8px;font-size:14px;line-height:1.5;color:#52667a">'
            . 'If the button does not work, copy and paste this address into your browser:</p>'
            . '<p style="margin:0 0 24px;font-size:13px;line-height:1.5;word-break:break-all">'
            . '<a href="' . $escape($url) . '" style="color:#0879c1">' . $escape($url) . '</a></p>'
            . '<hr style="border:0;border-top:1px solid #e5e9ed;margin:24px 0">'
            . '<p style="margin:0;font-size:13px;line-height:1.5;color:#6b7785">'
            . $escape($ignoreNotice) . '</p>'
            . '</td></tr></table></td></tr></table></body></html>';

        return array('text' => $text, 'html' => $html);
    }

    private function siteName()
    {
        $siteName = trim((string) $this->objConfig->getSiteName());
        return $siteName === '' ? 'This site' : $siteName;
    }

    private function username($value)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return $value !== '' && strlen($value) <= 255
            && preg_match('/^[a-z0-9][a-z0-9._-]*$/', $value) ? $value : null;
    }

    private function email($value)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return strlen($value) <= 320 && filter_var($value, FILTER_VALIDATE_EMAIL)
            ? $value : null;
    }

    private function identifier($value, $maximum)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value !== '' && strlen($value) <= $maximum
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/', $value)
            ? $value : null;
    }

    private function text($value, $maximum)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value !== '' && strlen($value) <= $maximum
            && !preg_match('/[\x00-\x1F\x7F]/', $value) ? $value : null;
    }

    private function quote($value)
    {
        $database = $this->objEngine->getDbObj();
        return method_exists($database, 'quoteSmart')
            ? $database->quoteSmart((string) $value)
            : "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function result($ok, $code, $pendingId = null)
    {
        return array('ok' => (bool) $ok, 'code' => $code, 'pendingId' => $pendingId);
    }
}
?>
