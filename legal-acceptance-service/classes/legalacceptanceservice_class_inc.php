<?php
/** Immutable storage boundary for versioned legal acceptance evidence. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class legalacceptanceservice extends dbTable
{
    private const TABLE_NAME = 'tbl_legal_acceptance_service_acceptances';
    private const SUBJECT_TYPES = array('user', 'pending_registration');
    private const METHODS = array('checkbox', 'signature', 'administrator', 'migration');

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
        $this->objAccountEvents = $this->getObject(
            'accounteventservice',
            'account-event-service'
        );
    }

    /** Record one exact acceptance idempotently. Evidence is never updated. */
    public function recordAcceptance(array $input)
    {
        $values = $this->normalise($input);
        if ($values === null) {
            return $this->result(false, 'invalid_acceptance');
        }

        $existing = $this->findExact($values);
        if ($existing !== null) {
            return $this->result(true, 'already_recorded', $existing['id']);
        }

        $values['id'] = bin2hex(random_bytes(16));
        $values['recorded_at'] = date('Y-m-d H:i:s');
        $this->beginTransaction();
        if ($this->insert($values) === false) {
            $this->rollbackTransaction();
            $existing = $this->findExact($values);
            return $existing !== null
                ? $this->result(true, 'already_recorded', $existing['id'])
                : $this->result(false, 'acceptance_record_failed');
        }

        $event = $this->objAccountEvents->append(array(
            'eventType' => 'legal.acceptance.recorded',
            'subjectType' => $values['subject_type'],
            'subjectId' => $values['subject_id'],
            'actorType' => $values['subject_type'] === 'user' ? 'user' : 'anonymous',
            'actorId' => $values['subject_type'] === 'user'
                ? $values['subject_id'] : '',
            'outcome' => 'succeeded',
            'correlationId' => $values['correlation_id'],
            'sourceService' => 'legal-acceptance-service',
            'occurredAt' => $values['accepted_at'],
            'metadata' => array(
                'policy_key' => $values['policy_key'],
                'policy_version' => $values['policy_version'],
                'content_digest' => $values['content_digest'],
            ),
        ));

        if (empty($event['ok'])) {
            $this->rollbackTransaction();
            return $this->result(false, 'acceptance_audit_failed');
        }
        $this->commitTransaction();
        return $this->result(true, 'acceptance_recorded', $values['id']);
    }

    public function hasAccepted(
        $subjectType,
        $subjectId,
        $policyKey,
        $policyVersion,
        $contentDigest = null
    ) {
        $subjectType = $this->enumValue($subjectType, self::SUBJECT_TYPES);
        $subjectId = $this->textValue($subjectId, 191);
        $policyKey = $this->identifier($policyKey, 96);
        $policyVersion = $this->identifier($policyVersion, 64);
        $contentDigest = $contentDigest === null
            ? null : $this->digest($contentDigest);
        if ($subjectType === null || $subjectId === null || $policyKey === null
            || $policyVersion === null
            || ($contentDigest === null && func_num_args() >= 5)) {
            return false;
        }

        $sql = 'SELECT id FROM ' . self::TABLE_NAME
            . ' WHERE subject_type = ' . $this->quoteValue($subjectType)
            . ' AND subject_id = ' . $this->quoteValue($subjectId)
            . ' AND policy_key = ' . $this->quoteValue($policyKey)
            . ' AND policy_version = ' . $this->quoteValue($policyVersion);
        if ($contentDigest !== null) {
            $sql .= ' AND content_digest = ' . $this->quoteValue($contentDigest);
        }
        $rows = $this->getArray($sql . ' LIMIT 1');
        return is_array($rows) && count($rows) === 1;
    }

    public function history($subjectType, $subjectId, $policyKey, $limit = 100)
    {
        $subjectType = $this->enumValue($subjectType, self::SUBJECT_TYPES);
        $subjectId = $this->textValue($subjectId, 191);
        $policyKey = $this->identifier($policyKey, 96);
        $limit = filter_var($limit, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1, 'max_range' => 500),
        ));
        if ($subjectType === null || $subjectId === null
            || $policyKey === null || $limit === false) {
            return array();
        }
        $sql = 'SELECT id, subject_type, subject_id, policy_key, policy_version,'
            . ' content_digest, acceptance_method, accepted_at, recorded_at FROM '
            . self::TABLE_NAME
            . ' WHERE subject_type = ' . $this->quoteValue($subjectType)
            . ' AND subject_id = ' . $this->quoteValue($subjectId)
            . ' AND policy_key = ' . $this->quoteValue($policyKey)
            . ' ORDER BY accepted_at DESC, recorded_at DESC, id DESC LIMIT '
            . (int) $limit;
        $rows = $this->getArray($sql);
        return is_array($rows) ? $rows : array();
    }

    private function normalise(array $input)
    {
        $values = array(
            'subject_type' => $this->enumValue(
                $input['subjectType'] ?? null,
                self::SUBJECT_TYPES
            ),
            'subject_id' => $this->textValue($input['subjectId'] ?? null, 191),
            'policy_key' => $this->identifier($input['policyKey'] ?? null, 96),
            'policy_version' => $this->identifier($input['policyVersion'] ?? null, 64),
            'content_digest' => $this->digest($input['contentDigest'] ?? null),
            'acceptance_method' => $this->enumValue(
                $input['acceptanceMethod'] ?? null,
                self::METHODS
            ),
            'ip_address' => $this->ipAddress($input['ipAddress'] ?? ''),
            'user_agent' => $this->textValue($input['userAgent'] ?? '', 512, true),
            'locale' => $this->identifier($input['locale'] ?? '', 32, true),
            'correlation_id' => $this->identifier($input['correlationId'] ?? null, 64),
            'accepted_at' => $this->timestamp($input['acceptedAt'] ?? null),
        );
        if (in_array(null, $values, true)) {
            return null;
        }
        foreach (array('ip_address', 'user_agent', 'locale') as $optional) {
            $values[$optional] = $values[$optional] === '' ? null : $values[$optional];
        }
        return $values;
    }

    private function findExact(array $values)
    {
        $sql = 'SELECT id FROM ' . self::TABLE_NAME
            . ' WHERE subject_type = ' . $this->quoteValue($values['subject_type'])
            . ' AND subject_id = ' . $this->quoteValue($values['subject_id'])
            . ' AND policy_key = ' . $this->quoteValue($values['policy_key'])
            . ' AND policy_version = ' . $this->quoteValue($values['policy_version'])
            . ' AND content_digest = ' . $this->quoteValue($values['content_digest'])
            . ' LIMIT 2';
        $rows = $this->getArray($sql);
        return is_array($rows) && count($rows) === 1 ? $rows[0] : null;
    }

    private function digest($value)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return preg_match('/^[a-f0-9]{64}$/', $value) ? $value : null;
    }

    private function ipAddress($value)
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return is_scalar($value) && filter_var((string) $value, FILTER_VALIDATE_IP)
            ? (string) $value : null;
    }

    private function timestamp($value)
    {
        if ($value === null || $value === '') {
            return date('Y-m-d H:i:s');
        }
        if (!is_scalar($value)) {
            return null;
        }
        $value = (string) $value;
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        return $parsed !== false && $parsed->format('Y-m-d H:i:s') === $value
            ? $value : null;
    }

    private function enumValue($value, array $allowed)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return in_array($value, $allowed, true) ? $value : null;
    }

    private function identifier($value, $maximum, $allowEmpty = false)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '') {
            return $allowEmpty ? '' : null;
        }
        return strlen($value) <= $maximum
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/', $value)
            ? $value : null;
    }

    private function textValue($value, $maximum, $allowEmpty = false)
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return $allowEmpty ? '' : null;
        }
        return strlen($value) <= $maximum
            && !preg_match('/[\x00-\x1F\x7F]/', $value)
            ? $value : null;
    }

    private function quoteValue($value)
    {
        return method_exists($this->_objDB, 'quoteSmart')
            ? $this->_objDB->quoteSmart((string) $value)
            : "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function result($ok, $code, $acceptanceId = null)
    {
        return array('ok' => (bool) $ok, 'code' => $code, 'acceptanceId' => $acceptanceId);
    }
}
?>
