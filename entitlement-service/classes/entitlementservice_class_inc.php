<?php
/** Immutable entitlement grant and revocation ledger. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class entitlementservice extends dbTable
{
    private const GRANTS = 'tbl_entitlement_service_grants';
    private const REVOCATIONS = 'tbl_entitlement_service_revocations';
    private const ACTOR_TYPES = array('user', 'service', 'system');

    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorHandler'
    ) {
        parent::init(
            $tableName !== null ? $tableName : self::GRANTS,
            $pearDb,
            $errorCallback
        );
        $this->objUsers = $this->getObject('userservice', 'security');
        $this->objEvents = $this->getObject(
            'accounteventservice',
            'account-event-service'
        );
    }

    public function grant(array $input, $manageTransaction = true)
    {
        $values = $this->normaliseGrant($input);
        if ($values === null) {
            return $this->result(false, 'invalid_grant');
        }
        if ($this->objUsers->findByUserId($values['user_id']) === null) {
            return $this->result(false, 'user_not_found');
        }
        $existing = $this->rowBy('idempotency_key', $values['idempotency_key']);
        if ($existing !== null) {
            return $this->result(true, 'already_granted', $existing['id']);
        }
        $values['id'] = bin2hex(random_bytes(16));
        $values['granted_at'] = date('Y-m-d H:i:s');
        if ($manageTransaction) {
            $this->beginTransaction();
        }
        if ($this->insert($values) === false) {
            if ($manageTransaction) {
                $this->rollbackTransaction();
            }
            $existing = $this->rowBy('idempotency_key', $values['idempotency_key']);
            return $existing === null
                ? $this->result(false, 'grant_failed')
                : $this->result(true, 'already_granted', $existing['id']);
        }
        if (!$this->appendEvent(
            'entitlement.granted',
            $values['user_id'],
            $values['correlation_id'],
            $values['entitlement_type'],
            $values['resource_type'],
            $values['resource_id']
        )) {
            if ($manageTransaction) {
                $this->rollbackTransaction();
            }
            return $this->result(false, 'grant_audit_failed');
        }
        if ($manageTransaction) {
            $this->commitTransaction();
        }
        return $this->result(true, 'entitlement_granted', $values['id']);
    }

    public function revoke(
        $grantId,
        $reasonCode,
        array $actor,
        $correlationId,
        $manageTransaction = true
    )
    {
        $grantId = $this->hexId($grantId);
        $reasonCode = $this->identifier($reasonCode, 96);
        $actorType = $this->enumValue($actor['type'] ?? null, self::ACTOR_TYPES);
        $actorId = $this->text($actor['id'] ?? '', 191, true);
        $correlationId = $this->identifier($correlationId, 64);
        if ($grantId === null || $reasonCode === null || $actorType === null
            || $actorId === null || $correlationId === null
            || ($actorType === 'user' && $actorId === '')) {
            return $this->result(false, 'invalid_revocation');
        }
        $grant = $this->rowBy('id', $grantId);
        if ($grant === null) {
            return $this->result(false, 'grant_not_found');
        }
        $existing = $this->revocationForGrant($grantId);
        if ($existing !== null) {
            return $this->result(true, 'already_revoked', $grantId);
        }
        if ($manageTransaction) {
            $this->beginTransaction();
        }
        $previous = $this->_tableName;
        $this->_tableName = self::REVOCATIONS;
        $inserted = $this->insert(array(
            'id' => bin2hex(random_bytes(16)),
            'grant_id' => $grantId,
            'reason_code' => $reasonCode,
            'correlation_id' => $correlationId,
            'revoked_at' => date('Y-m-d H:i:s'),
            'revoked_by_type' => $actorType,
            'revoked_by_id' => $actorId === '' ? null : $actorId,
        ));
        $this->_tableName = $previous;
        if ($inserted === false || !$this->appendEvent(
            'entitlement.revoked',
            $grant['user_id'],
            $correlationId,
            $grant['entitlement_type'],
            $grant['resource_type'],
            $grant['resource_id']
        )) {
            if ($manageTransaction) {
                $this->rollbackTransaction();
            }
            return $this->result(false, 'revocation_failed', $grantId);
        }
        if ($manageTransaction) {
            $this->commitTransaction();
        }
        return $this->result(true, 'entitlement_revoked', $grantId);
    }

    public function hasEntitlement(
        $userId,
        $entitlementType,
        $resourceType,
        $resourceId,
        $at = null
    ) {
        $userId = $this->text($userId, 25);
        $entitlementType = $this->identifier($entitlementType, 96);
        $resourceType = $this->identifier($resourceType, 96);
        $resourceId = $this->text($resourceId, 191);
        $at = $this->timestamp($at);
        if ($userId === null || $entitlementType === null
            || $resourceType === null || $resourceId === null || $at === null) {
            return false;
        }
        $rows = $this->getArray(
            'SELECT g.id FROM ' . self::GRANTS . ' AS g'
            . ' LEFT JOIN ' . self::REVOCATIONS . ' AS r ON r.grant_id = g.id'
            . ' WHERE g.user_id = ' . $this->quote($userId)
            . ' AND g.entitlement_type = ' . $this->quote($entitlementType)
            . ' AND g.resource_type = ' . $this->quote($resourceType)
            . ' AND g.resource_id = ' . $this->quote($resourceId)
            . ' AND g.effective_at <= ' . $this->quote($at)
            . ' AND (g.expires_at IS NULL OR g.expires_at > ' . $this->quote($at) . ')'
            . ' AND r.id IS NULL LIMIT 1'
        );
        return is_array($rows) && count($rows) === 1;
    }

    public function activeForUser($userId, $at = null, $limit = 100)
    {
        $userId = $this->text($userId, 25);
        $at = $this->timestamp($at);
        $limit = filter_var($limit, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1, 'max_range' => 500),
        ));
        if ($userId === null || $at === null || $limit === false) {
            return array();
        }
        $rows = $this->getArray(
            'SELECT g.* FROM ' . self::GRANTS . ' AS g'
            . ' LEFT JOIN ' . self::REVOCATIONS . ' AS r ON r.grant_id = g.id'
            . ' WHERE g.user_id = ' . $this->quote($userId)
            . ' AND g.effective_at <= ' . $this->quote($at)
            . ' AND (g.expires_at IS NULL OR g.expires_at > ' . $this->quote($at) . ')'
            . ' AND r.id IS NULL ORDER BY g.effective_at DESC, g.id DESC LIMIT '
            . (int) $limit
        );
        return is_array($rows) ? $rows : array();
    }

    public function historyForUser($userId, $limit = 100)
    {
        $userId = $this->text($userId, 25);
        $limit = filter_var($limit, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1, 'max_range' => 500),
        ));
        if ($userId === null || $limit === false) {
            return array();
        }
        $rows = $this->getArray(
            'SELECT g.*, r.id AS revocation_id, r.reason_code,'
            . ' r.revoked_at, r.revoked_by_type, r.revoked_by_id'
            . ' FROM ' . self::GRANTS . ' AS g LEFT JOIN '
            . self::REVOCATIONS . ' AS r ON r.grant_id = g.id'
            . ' WHERE g.user_id = ' . $this->quote($userId)
            . ' ORDER BY g.granted_at DESC, g.id DESC LIMIT ' . (int) $limit
        );
        return is_array($rows) ? $rows : array();
    }

    /** Resolve one grant owned by its service-level idempotency key. */
    public function grantByIdempotencyKey($idempotencyKey)
    {
        $idempotencyKey = $this->text($idempotencyKey, 191);
        return $idempotencyKey === null
            ? null : $this->rowBy('idempotency_key', $idempotencyKey);
    }

    /** Return the latest unrevoked grant in one service-owned key family. */
    public function latestUnrevokedGrantByIdempotencyPrefix($prefix)
    {
        $prefix = $this->text($prefix, 150);
        if ($prefix === null) {
            return null;
        }
        $rows = $this->getArray(
            'SELECT g.* FROM ' . self::GRANTS . ' g'
            . ' LEFT JOIN ' . self::REVOCATIONS . ' r ON r.grant_id = g.id'
            . ' WHERE r.id IS NULL AND (g.idempotency_key = '
            . $this->quote($prefix) . ' OR g.idempotency_key LIKE '
            . $this->quote($prefix . ':amend:%') . ')'
            . ' ORDER BY g.granted_at DESC, g.id DESC LIMIT 1'
        );
        return is_array($rows) && count($rows) === 1 ? $rows[0] : null;
    }

    private function normaliseGrant(array $input)
    {
        $metadata = $input['metadata'] ?? array();
        if (!is_array($metadata) || $this->containsSecretKey($metadata)) {
            return null;
        }
        $metadataJson = json_encode(
            $metadata,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $values = array(
            'user_id' => $this->text($input['userId'] ?? null, 25),
            'entitlement_type' => $this->identifier($input['entitlementType'] ?? null, 96),
            'resource_type' => $this->identifier($input['resourceType'] ?? null, 96),
            'resource_id' => $this->text($input['resourceId'] ?? null, 191),
            'source_type' => $this->identifier($input['sourceType'] ?? null, 96),
            'source_reference' => $this->text($input['sourceReference'] ?? '', 191, true),
            'idempotency_key' => $this->text($input['idempotencyKey'] ?? null, 191),
            'correlation_id' => $this->identifier($input['correlationId'] ?? null, 64),
            'metadata_json' => $metadataJson === '[]' ? null : $metadataJson,
            'effective_at' => $this->timestamp($input['effectiveAt'] ?? null),
            'expires_at' => ($input['expiresAt'] ?? null) === null
                ? null : $this->timestamp($input['expiresAt']),
            'granted_by_type' => $this->enumValue(
                $input['grantedByType'] ?? null,
                self::ACTOR_TYPES
            ),
            'granted_by_id' => $this->text($input['grantedById'] ?? '', 191, true),
        );
        if (($values['source_type'] ?? null) === 'manual'
            && $this->text($metadata['reason'] ?? null, 500) === null) {
            return null;
        }
        if ($metadataJson === false || strlen($metadataJson) > 65535
            || in_array(null, array_diff_key($values, array_flip(array(
                'metadata_json', 'expires_at'
            ))), true)
            || ($values['granted_by_type'] === 'user' && $values['granted_by_id'] === '')
            || ($values['expires_at'] !== null
                && $values['expires_at'] <= $values['effective_at'])) {
            return null;
        }
        $values['source_reference'] = $values['source_reference'] === ''
            ? null : $values['source_reference'];
        $values['granted_by_id'] = $values['granted_by_id'] === ''
            ? null : $values['granted_by_id'];
        return $values;
    }

    private function containsSecretKey(array $values)
    {
        $blocked = array(
            'password', 'pass', 'password_hash', 'credential', 'secret',
            'token', 'api_key', 'authorization', 'cookie', 'recovery_code'
        );
        foreach ($values as $key => $value) {
            $key = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) $key)), '_');
            if (in_array($key, $blocked, true)
                || (is_array($value) && $this->containsSecretKey($value))
                || is_object($value) || is_resource($value)) {
                return true;
            }
        }
        return false;
    }

    private function appendEvent($type, $userId, $correlationId, $entitlement, $resourceType, $resourceId)
    {
        $event = $this->objEvents->append(array(
            'eventType' => $type,
            'subjectType' => 'user',
            'subjectId' => $userId,
            'actorType' => 'system',
            'actorId' => 'entitlement-service',
            'outcome' => 'succeeded',
            'correlationId' => $correlationId,
            'sourceService' => 'entitlement-service',
            'metadata' => array(
                'entitlement_type' => $entitlement,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
            ),
        ));
        return !empty($event['ok']);
    }

    private function rowBy($column, $value)
    {
        if (!in_array($column, array('id', 'idempotency_key'), true)) {
            return null;
        }
        $rows = $this->getArray(
            'SELECT * FROM ' . self::GRANTS . ' WHERE ' . $column . ' = '
            . $this->quote($value) . ' LIMIT 2'
        );
        return is_array($rows) && count($rows) === 1 ? $rows[0] : null;
    }

    private function revocationForGrant($grantId)
    {
        $rows = $this->getArray(
            'SELECT id FROM ' . self::REVOCATIONS
            . ' WHERE grant_id = ' . $this->quote($grantId) . ' LIMIT 2'
        );
        return is_array($rows) && count($rows) === 1 ? $rows[0] : null;
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

    private function hexId($value)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return preg_match('/^[a-f0-9]{32}$/', $value) ? $value : null;
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

    private function text($value, $maximum, $allowEmpty = false)
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return $allowEmpty ? '' : null;
        }
        return strlen($value) <= $maximum
            && !preg_match('/[\x00-\x1F\x7F]/', $value) ? $value : null;
    }

    private function quote($value)
    {
        $database = $this->objEngine->getDbObj();
        return method_exists($database, 'quoteSmart')
            ? $database->quoteSmart((string) $value)
            : "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function result($ok, $code, $grantId = null)
    {
        return array('ok' => (bool) $ok, 'code' => $code, 'grantId' => $grantId);
    }
}
?>
