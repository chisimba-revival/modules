<?php
/** Shared access resolver for course and member-page resources. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class accesspolicyservice extends ChisimbaObject
{
    private const POLICIES = array('public', 'free', 'tier_1', 'tier_2', 'private');
    private const RESOURCE_TYPES = array('course', 'page');

    public function init()
    {
        $this->objUsers = $this->getObject('userservice', 'security');
        $this->objMembership = $this->getObject('membershipservice', 'membership-service');
        $this->objEntitlements = $this->getObject('entitlementservice', 'entitlement-service');
    }

    public function resolve(array $request)
    {
        $policy = $this->enumValue($request['policy'] ?? null, self::POLICIES);
        $resourceType = $this->enumValue($request['resourceType'] ?? null, self::RESOURCE_TYPES);
        $resourceId = $this->text($request['resourceId'] ?? null, 191);
        $userId = $this->text($request['userId'] ?? '', 25, true);
        $at = $this->timestamp($request['at'] ?? null);
        if ($policy === null || $resourceType === null || $resourceId === null
            || $userId === null || $at === null) {
            return $this->decision(false, 'invalid_policy_request', $policy);
        }
        if ($policy === 'public') {
            return $this->decision(true, 'public_access', $policy);
        }
        if ($userId === '' || $this->objUsers->findByUserId($userId) === null) {
            return $this->decision(false, 'sign_in_required', $policy);
        }
        if ($policy === 'free') {
            return $this->decision(true, 'signed_in_access', $policy);
        }
        if ($policy === 'private') {
            $allowed = $this->objEntitlements->hasEntitlement(
                $userId,
                'resource_access',
                $resourceType,
                $resourceId,
                $at
            );
            return $this->decision(
                $allowed,
                $allowed ? 'explicit_resource_entitlement' : 'private_entitlement_required',
                $policy
            );
        }
        $heldTier = $this->objMembership->effectiveTier($userId, $at);
        $allowed = $this->objMembership->tierIncludes($heldTier, $policy);
        return $this->decision(
            $allowed,
            $allowed ? 'membership_tier_includes_policy' : 'membership_tier_required',
            $policy,
            $heldTier
        );
    }

    private function decision($allowed, $reason, $policy, $heldTier = null)
    {
        return array(
            'allowed' => (bool) $allowed,
            'reason' => $reason,
            'requiredPolicy' => $policy,
            'effectiveTier' => $heldTier,
        );
    }

    private function enumValue($value, array $allowed)
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        return in_array($value, $allowed, true) ? $value : null;
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
}
?>
