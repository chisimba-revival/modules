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
        $this->objConfig = $this->getObject('altconfig', 'config');
    }

    /** Create pending state only; no canonical account is created or activated. */
    public function createPending(array $request)
    {
        $username = $this->username($request['username'] ?? null);
        $email = $this->email($request['emailAddress'] ?? null);
        $firstName = $this->text($request['firstName'] ?? null, 50);
        $surname = $this->text($request['surname'] ?? null, 50);
        $correlationId = $this->identifier($request['correlationId'] ?? null, 64);
        $password = $request['password'] ?? null;
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
        if ($this->hasLivePending('username', $username)
            || $this->hasLivePending('email_address', $email)) {
            return $this->result(false, 'registration_already_pending');
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
        $message = $this->objCommunications->queueEmail(array(
            'to' => $pending['email_address'],
            'toName' => $pending['first_name'] . ' ' . $pending['surname'],
            'subject' => 'Verify your account',
            'text' => "Complete your account verification:\n" . $url,
            'idempotencyKey' => 'registration-verification:' . $token['tokenId'],
            'metadata' => array(
                'purpose' => 'registration_verification',
                'pendingRegistrationId' => $pending['id'],
            ),
        ));
        if (empty($message['ok'])) {
            return $this->result(false, 'verification_email_failed', $pending['id']);
        }
        $this->beginTransaction();
        if ($this->update('id', $pending['id'], array(
            'status' => 'awaiting_verification',
            'updated_at' => date('Y-m-d H:i:s'),
        )) === false || !$this->appendEvent(
            'registration.verification.requested',
            $pending['id'],
            $pending['correlation_id'],
            'requested'
        )) {
            $this->rollbackTransaction();
            return $this->result(false, 'verification_state_failed', $pending['id']);
        }
        $this->commitTransaction();
        return $this->result(true, 'verification_queued', $pending['id']);
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

    private function hasLivePending($column, $value)
    {
        $allowed = array('username', 'email_address');
        if (!in_array($column, $allowed, true)) {
            return true;
        }
        $rows = $this->getArray(
            'SELECT id FROM ' . self::TABLE_NAME
            . ' WHERE ' . $column . ' = ' . $this->quote($value)
            . " AND status IN ('awaiting_legal_acceptance','awaiting_verification','verified')"
            . ' AND expires_at > ' . $this->quote(date('Y-m-d H:i:s'))
            . ' LIMIT 1'
        );
        return is_array($rows) && count($rows) > 0;
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
        return method_exists($this->_objDB, 'quoteSmart')
            ? $this->_objDB->quoteSmart((string) $value)
            : "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function result($ok, $code, $pendingId = null)
    {
        return array('ok' => (bool) $ok, 'code' => $code, 'pendingId' => $pendingId);
    }
}
?>
