<?php
/** Hashed, expiring, single-use token boundary. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class registrationtokenservice extends dbTable
{
    private const TABLE_NAME = 'tbl_registration_service_tokens';
    private const PURPOSES = array('email_verification', 'password_recovery');
    private const SUBJECT_TYPES = array('pending_registration', 'user');

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
    }

    /** Raw token is returned once and is never persisted. */
    public function issue($purpose, $subjectType, $subjectId, $correlationId, $ttlSeconds)
    {
        $purpose = $this->enumValue($purpose, self::PURPOSES);
        $subjectType = $this->enumValue($subjectType, self::SUBJECT_TYPES);
        $subjectId = $this->text($subjectId, 191);
        $correlationId = $this->identifier($correlationId, 64);
        $ttlSeconds = filter_var($ttlSeconds, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 300, 'max_range' => 604800),
        ));
        if ($purpose === null || $subjectType === null || $subjectId === null
            || $correlationId === null || $ttlSeconds === false
            || ($purpose === 'email_verification' && $subjectType !== 'pending_registration')
            || ($purpose === 'password_recovery' && $subjectType !== 'user')) {
            return $this->result(false, 'invalid_token_request');
        }

        $now = date('Y-m-d H:i:s');
        $rawVerifier = bin2hex(random_bytes(32));
        $selector = bin2hex(random_bytes(12));
        $id = bin2hex(random_bytes(16));
        $this->beginTransaction();
        $superseded = $this->_execute(
            'UPDATE ' . self::TABLE_NAME
            . ' SET superseded_at = ' . $this->quote($now)
            . ' WHERE purpose = ' . $this->quote($purpose)
            . ' AND subject_type = ' . $this->quote($subjectType)
            . ' AND subject_id = ' . $this->quote($subjectId)
            . ' AND consumed_at IS NULL AND superseded_at IS NULL'
        );
        if ($superseded === false || $this->insert(array(
            'id' => $id,
            'purpose' => $purpose,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'selector' => $selector,
            'verifier_hash' => hash('sha256', $rawVerifier),
            'correlation_id' => $correlationId,
            'expires_at' => date('Y-m-d H:i:s', time() + (int) $ttlSeconds),
            'consumed_at' => null,
            'superseded_at' => null,
            'created_at' => $now,
        )) === false) {
            $this->rollbackTransaction();
            return $this->result(false, 'token_issue_failed');
        }
        $this->commitTransaction();
        return $this->result(true, 'token_issued', $id, $selector . '.' . $rawVerifier);
    }

    public function consume($purpose, $rawToken)
    {
        return $this->consumeWith($purpose, $rawToken, null);
    }

    /**
     * Consume a token and run its state transition in the same transaction.
     * The callback receives subjectType and subjectId and must return true.
     */
    public function consumeWith($purpose, $rawToken, $onConsume)
    {
        if ($onConsume !== null && !is_callable($onConsume)) {
            return $this->result(false, 'invalid_consumer');
        }
        $purpose = $this->enumValue($purpose, self::PURPOSES);
        if ($purpose === null || !is_scalar($rawToken)
            || !preg_match('/^([a-f0-9]{24})\.([a-f0-9]{64})$/', (string) $rawToken, $parts)) {
            return $this->result(false, 'invalid_token');
        }
        $this->beginTransaction();
        $rows = $this->getArray(
            'SELECT id, subject_type, subject_id, verifier_hash, expires_at'
            . ' FROM ' . self::TABLE_NAME
            . ' WHERE selector = ' . $this->quote($parts[1])
            . ' AND purpose = ' . $this->quote($purpose)
            . ' AND consumed_at IS NULL AND superseded_at IS NULL LIMIT 2 FOR UPDATE'
        );
        if (!is_array($rows) || count($rows) !== 1
            || strtotime($rows[0]['expires_at']) <= time()
            || !hash_equals($rows[0]['verifier_hash'], hash('sha256', $parts[2]))) {
            $this->rollbackTransaction();
            return $this->result(false, 'invalid_or_expired_token');
        }
        if ($onConsume !== null && call_user_func(
            $onConsume,
            $rows[0]['subject_type'],
            $rows[0]['subject_id']
        ) !== true) {
            $this->rollbackTransaction();
            return $this->result(false, 'token_transition_failed');
        }
        $consumedAt = date('Y-m-d H:i:s');
        if ($this->_execute(
            'UPDATE ' . self::TABLE_NAME
            . ' SET consumed_at = ' . $this->quote($consumedAt)
            . ' WHERE id = ' . $this->quote($rows[0]['id'])
            . ' AND consumed_at IS NULL AND superseded_at IS NULL'
        ) === false) {
            $this->rollbackTransaction();
            return $this->result(false, 'token_consume_failed');
        }
        $this->commitTransaction();
        return array(
            'ok' => true,
            'code' => 'token_consumed',
            'tokenId' => $rows[0]['id'],
            'subjectType' => $rows[0]['subject_type'],
            'subjectId' => $rows[0]['subject_id'],
            'rawToken' => null,
        );
    }

    private function enumValue($value, array $allowed)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return in_array($value, $allowed, true) ? $value : null;
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

    private function result($ok, $code, $tokenId = null, $rawToken = null)
    {
        return array(
            'ok' => (bool) $ok,
            'code' => $code,
            'tokenId' => $tokenId,
            'rawToken' => $rawToken,
        );
    }
}
?>
