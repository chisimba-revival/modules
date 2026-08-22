<?php
/**
 * Append-only boundary for sanitized account events.
 *
 * There are deliberately no update or delete operations. Unsafe metadata is
 * rejected rather than silently redacted or partially persisted.
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class accounteventservice extends dbTable
{
    private const TABLE_NAME = 'tbl_account_event_service_events';
    private const SUBJECT_TYPES = array('user', 'pending_registration', 'anonymous');
    private const ACTOR_TYPES = array('user', 'service', 'system', 'anonymous');
    private const OUTCOMES = array('succeeded', 'failed', 'denied', 'requested');
    private const SECRET_KEYS = array(
        'password', 'pass', 'password_hash', 'passwordhash', 'credential',
        'secret', 'token', 'raw_token', 'verification_token', 'reset_token',
        'recovery_code', 'mfa_secret', 'authorization', 'cookie'
    );

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

    public function append(array $event)
    {
        $normalised = $this->normaliseEvent($event);
        if (!$normalised['ok']) {
            return $normalised;
        }

        $values = $normalised['values'];
        $values['id'] = bin2hex(random_bytes(16));
        $values['recorded_at'] = date('Y-m-d H:i:s');
        if ($this->insert($values) === false) {
            return $this->result(false, 'event_append_failed');
        }

        return $this->result(true, 'event_appended', $values['id']);
    }

    /** Authorization belongs to the application boundary calling this read. */
    public function forSubject($subjectType, $subjectId, $limit = 100)
    {
        $subjectType = $this->enumValue($subjectType, self::SUBJECT_TYPES);
        $subjectId = $this->textValue($subjectId, 191);
        $limit = filter_var($limit, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1, 'max_range' => 500),
        ));
        if ($subjectType === null || $subjectId === null || $limit === false) {
            return array();
        }

        $sql = 'SELECT id, event_type, subject_type, subject_id, actor_type,'
            . ' actor_id, outcome, reason_code, correlation_id, source_service,'
            . ' metadata_json, occurred_at, recorded_at FROM ' . self::TABLE_NAME
            . ' WHERE subject_type = ' . $this->quoteValue($subjectType)
            . ' AND subject_id = ' . $this->quoteValue($subjectId)
            . ' ORDER BY occurred_at DESC, recorded_at DESC, id DESC'
            . ' LIMIT ' . (int) $limit;
        $rows = $this->getArray($sql);

        return is_array($rows) ? $rows : array();
    }

    private function normaliseEvent(array $event)
    {
        $values = array(
            'event_type' => $this->identifier($event['eventType'] ?? null, 96),
            'subject_type' => $this->enumValue(
                $event['subjectType'] ?? null,
                self::SUBJECT_TYPES
            ),
            'subject_id' => $this->textValue($event['subjectId'] ?? null, 191),
            'actor_type' => $this->enumValue(
                $event['actorType'] ?? null,
                self::ACTOR_TYPES
            ),
            'actor_id' => $this->textValue($event['actorId'] ?? '', 191, true),
            'outcome' => $this->enumValue($event['outcome'] ?? null, self::OUTCOMES),
            'reason_code' => $this->identifier($event['reasonCode'] ?? '', 96, true),
            'correlation_id' => $this->identifier(
                $event['correlationId'] ?? null,
                64
            ),
            'source_service' => $this->identifier(
                $event['sourceService'] ?? null,
                96
            ),
            'occurred_at' => $this->timestamp($event['occurredAt'] ?? null),
        );
        if (in_array(null, $values, true)) {
            return $this->result(false, 'invalid_event');
        }
        if ($values['actor_type'] === 'user' && $values['actor_id'] === '') {
            return $this->result(false, 'actor_id_required');
        }

        $metadata = $event['metadata'] ?? array();
        if (!is_array($metadata) || $this->containsSecretKey($metadata)) {
            return $this->result(false, 'unsafe_metadata');
        }
        $json = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || strlen($json) > 65535) {
            return $this->result(false, 'invalid_metadata');
        }
        $values['actor_id'] = $values['actor_id'] === '' ? null : $values['actor_id'];
        $values['reason_code'] = $values['reason_code'] === '' ? null : $values['reason_code'];
        $values['metadata_json'] = $json === '[]' ? null : $json;

        return array('ok' => true, 'values' => $values);
    }

    private function containsSecretKey(array $values)
    {
        foreach ($values as $key => $value) {
            $key = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) $key)), '_');
            if (in_array($key, self::SECRET_KEYS, true)) {
                return true;
            }
            if ((is_array($value) && $this->containsSecretKey($value))
                || is_object($value) || is_resource($value)) {
                return true;
            }
        }
        return false;
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

    private function result($ok, $code, $eventId = null)
    {
        return array('ok' => (bool) $ok, 'code' => $code, 'eventId' => $eventId);
    }
}
?>

