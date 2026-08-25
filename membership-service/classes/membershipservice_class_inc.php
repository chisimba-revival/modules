<?php
/** Provider-neutral membership periods and ordered tier inheritance. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class membershipservice extends dbTable
{
    private const PERIODS = 'tbl_membership_service_periods';
    private const TIERS = array('free' => 0, 'tier_1' => 1, 'tier_2' => 2);
    private const STATES = array('scheduled', 'active', 'grace', 'expired');
    private const TRANSITIONS = array(
        'scheduled' => array('active', 'expired'),
        'active' => array('grace', 'expired'),
        'grace' => array('active', 'expired'),
        'expired' => array(),
    );

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : self::PERIODS, $pearDb, $errorCallback);
        $this->objUsers = $this->getObject('userservice', 'security');
        $this->objEntitlements = $this->getObject('entitlementservice', 'entitlement-service');
        $this->objEvents = $this->getObject('accounteventservice', 'account-event-service');
    }

    public function tierIncludes($heldTier, $requiredTier)
    {
        $held = $this->tier($heldTier);
        $required = $this->tier($requiredTier);
        return $held !== null && $required !== null
            && self::TIERS[$held] >= self::TIERS[$required];
    }

    public function effectiveTier($userId, $at = null)
    {
        $userId = $this->text($userId, 25);
        $at = $this->timestamp($at, true);
        if ($userId === null || $at === null) {
            return null;
        }
        $rows = $this->objEntitlements->activeForUser($userId, $at, 500);
        $best = 'free';
        foreach ((array) $rows as $row) {
            if (($row['entitlement_type'] ?? null) !== 'membership_tier'
                || ($row['resource_type'] ?? null) !== 'membership_tier') {
                continue;
            }
            $tier = $this->tier($row['resource_id'] ?? null);
            if ($tier !== null && self::TIERS[$tier] > self::TIERS[$best]) {
                $best = $tier;
            }
        }
        return $best;
    }

    public function createPeriod(array $input)
    {
        $values = $this->normalisePeriod($input);
        if ($values === null) {
            return $this->result(false, 'invalid_period');
        }
        if ($this->objUsers->findByUserId($values['user_id']) === null) {
            return $this->result(false, 'user_not_found');
        }
        $existing = $this->rowBy('idempotency_key', $values['idempotency_key']);
        if ($existing !== null) {
            return $this->result(true, 'already_created', $existing['id']);
        }
        $values['id'] = bin2hex(random_bytes(16));
        $values['created_at'] = $values['updated_at'] = date('Y-m-d H:i:s');
        $this->beginTransaction();
        if ($this->insert($values) === false) {
            $this->rollbackTransaction();
            $existing = $this->rowBy('idempotency_key', $values['idempotency_key']);
            return $existing === null
                ? $this->result(false, 'period_failed')
                : $this->result(true, 'already_created', $existing['id']);
        }
        if (!$this->grantTier($values) || !$this->appendEvent('membership.period_created', $values)) {
            $this->rollbackTransaction();
            return $this->result(false, 'period_side_effect_failed');
        }
        $this->commitTransaction();
        return $this->result(true, 'period_created', $values['id']);
    }

    public function transition($periodId, $nextState, $correlationId, $graceEndsAt = null)
    {
        $periodId = $this->hexId($periodId);
        $nextState = $this->state($nextState);
        $correlationId = $this->identifier($correlationId, 64);
        if ($periodId === null || $nextState === null || $correlationId === null) {
            return $this->result(false, 'invalid_transition');
        }
        $period = $this->rowBy('id', $periodId);
        if ($period === null) {
            return $this->result(false, 'period_not_found');
        }
        if ($period['state'] === $nextState) {
            return $this->result(true, 'already_transitioned', $periodId);
        }
        if (!in_array($nextState, self::TRANSITIONS[$period['state']] ?? array(), true)) {
            return $this->result(false, 'transition_not_allowed', $periodId);
        }
        $graceEndsAt = $nextState === 'grace' ? $this->timestamp($graceEndsAt) : null;
        if ($nextState === 'grace' && ($graceEndsAt === null || $graceEndsAt <= $period['ends_at'])) {
            return $this->result(false, 'invalid_grace', $periodId);
        }
        $this->beginTransaction();
        $updated = $this->update(
            array(
                'state' => $nextState,
                'grace_ends_at' => $graceEndsAt,
                'correlation_id' => $correlationId,
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            'id = ' . $this->quote($periodId) . ' AND state = ' . $this->quote($period['state'])
        );
        $period['state'] = $nextState;
        $period['correlation_id'] = $correlationId;
        $sideEffectOk = true;
        if ($nextState === 'grace') {
            $extension = $period;
            $extension['id'] = $period['id'] . ':grace';
            $extension['starts_at'] = $period['ends_at'];
            $extension['ends_at'] = $graceEndsAt;
            $extension['source_type'] = 'membership_grace';
            $sideEffectOk = $this->grantTier($extension);
        }
        if ($updated === false || !$sideEffectOk
            || !$this->appendEvent('membership.period_' . $nextState, $period)) {
            $this->rollbackTransaction();
            return $this->result(false, 'transition_failed', $periodId);
        }
        $this->commitTransaction();
        return $this->result(true, 'period_' . $nextState, $periodId);
    }

    private function grantTier(array $period)
    {
        $grant = $this->objEntitlements->grant(array(
            'userId' => $period['user_id'],
            'entitlementType' => 'membership_tier',
            'resourceType' => 'membership_tier',
            'resourceId' => $period['tier_code'],
            'sourceType' => $period['source_type'],
            'sourceReference' => $period['source_reference'] ?? '',
            'idempotencyKey' => 'membership-period:' . $period['id'],
            'correlationId' => $period['correlation_id'],
            'effectiveAt' => $period['starts_at'],
            'expiresAt' => $period['ends_at'],
            'grantedByType' => 'service',
            'grantedById' => 'membership-service',
            'metadata' => array('membership_period_id' => $period['id']),
        ));
        return !empty($grant['ok']);
    }

    private function normalisePeriod(array $input)
    {
        $startsAt = $this->timestamp($input['startsAt'] ?? null);
        $endsAt = $this->timestamp($input['endsAt'] ?? null);
        $values = array(
            'user_id' => $this->text($input['userId'] ?? null, 25),
            'tier_code' => $this->tier($input['tier'] ?? null),
            'state' => $this->state($input['state'] ?? 'scheduled'),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'grace_ends_at' => null,
            'source_type' => $this->identifier($input['sourceType'] ?? null, 96),
            'source_reference' => $this->text($input['sourceReference'] ?? '', 191, true),
            'idempotency_key' => $this->text($input['idempotencyKey'] ?? null, 191),
            'correlation_id' => $this->identifier($input['correlationId'] ?? null, 64),
        );
        if (in_array(null, array_diff_key($values, array_flip(array(
            'grace_ends_at', 'source_reference'
        ))), true) || $values['tier_code'] === 'free' || $endsAt <= $startsAt) {
            return null;
        }
        $values['source_reference'] = $values['source_reference'] === ''
            ? null : $values['source_reference'];
        return $values;
    }

    private function appendEvent($type, array $period)
    {
        $event = $this->objEvents->append(array(
            'eventType' => $type,
            'subjectType' => 'user',
            'subjectId' => $period['user_id'],
            'actorType' => 'system',
            'actorId' => 'membership-service',
            'outcome' => 'succeeded',
            'correlationId' => $period['correlation_id'],
            'sourceService' => 'membership-service',
            'metadata' => array(
                'membership_period_id' => $period['id'],
                'tier' => $period['tier_code'],
                'state' => $period['state'],
            ),
        ));
        return !empty($event['ok']);
    }

    private function rowBy($column, $value)
    {
        if (!in_array($column, array('id', 'idempotency_key'), true)) {
            return null;
        }
        $rows = $this->getArray('SELECT * FROM ' . self::PERIODS . ' WHERE '
            . $column . ' = ' . $this->quote($value) . ' LIMIT 2');
        return is_array($rows) && count($rows) === 1 ? $rows[0] : null;
    }

    private function tier($value)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return array_key_exists($value, self::TIERS) ? $value : null;
    }

    private function state($value)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return in_array($value, self::STATES, true) ? $value : null;
    }

    private function timestamp($value, $defaultNow = false)
    {
        if ($defaultNow && ($value === null || $value === '')) {
            return date('Y-m-d H:i:s');
        }
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        return $parsed !== false && $parsed->format('Y-m-d H:i:s') === $value ? $value : null;
    }

    private function identifier($value, $maximum)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value !== '' && strlen($value) <= $maximum
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/', $value) ? $value : null;
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
        return strlen($value) <= $maximum && !preg_match('/[\x00-\x1F\x7F]/', $value)
            ? $value : null;
    }

    private function hexId($value)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return preg_match('/^[a-f0-9]{32}$/', $value) ? $value : null;
    }

    private function quote($value)
    {
        $database = $this->objEngine->getDbObj();
        return method_exists($database, 'quoteSmart')
            ? $database->quoteSmart((string) $value)
            : "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function result($ok, $code, $periodId = null)
    {
        return array('ok' => (bool) $ok, 'code' => $code, 'periodId' => $periodId);
    }
}
?>
