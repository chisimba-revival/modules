<?php
/** Capability-based authorization and default operational role provisioning. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class membershipauthorizationservice extends dbTable
{
    public const ROLE_NAME = 'Membership Manager';

    private const CAPABILITIES = array(
        'membership.view' => 'View memberships and membership history',
        'membership.manage' => 'Create, extend and transition memberships',
        'membership.override' => 'Grant an audited manual membership override',
        'private_admission.manage' => 'Manage explicit private-course admission',
        'payment.view' => 'View payment status and payment event history',
        'payment.refund' => 'Issue refunds when a provider supports them',
    );

    private const DEFAULT_ROLE_CAPABILITIES = array(
        'membership.view',
        'membership.manage',
        'membership.override',
        'private_admission.manage',
        'payment.view',
    );

    public function init($tableName = null, $pearDb = null,
        $errorCallback = 'globalPearErrorHandler')
    {
        parent::init(
            $tableName !== null ? $tableName : 'tbl_permissions_acl',
            $pearDb,
            $errorCallback
        );
        $this->objUser = $this->getObject('user', 'security');
        $this->objIdentity = $this->getObject('identityservice', 'security');
        $this->objGroups = $this->getObject('groupservice', 'groupadmin');
        $this->objPermissions = $this->getObject('permissions_model', 'permissions');
        $this->objEvents = $this->getObject('accounteventservice', 'account-event-service');
    }

    public function capabilities()
    {
        return self::CAPABILITIES;
    }

    public function defaultRoleCapabilities()
    {
        return self::DEFAULT_ROLE_CAPABILITIES;
    }

    public function defaultRoleMembers()
    {
        if (!$this->objUser->isAdmin()) {
            return array();
        }
        $groupId = $this->objGroups->groupIdForName(self::ROLE_NAME);
        return $groupId ? $this->objGroups->getMembers($groupId) : array();
    }

    public function availableDefaultRoleUsers()
    {
        if (!$this->objUser->isAdmin()) {
            return array();
        }
        $groupId = $this->objGroups->groupIdForName(self::ROLE_NAME);
        return $groupId ? $this->objGroups->getAvailableUsers($groupId) : array();
    }

    public function can($capability)
    {
        $capability = $this->capability($capability);
        if ($capability === null || !$this->objUser->isLoggedIn()) {
            return false;
        }
        if ($this->objUser->isAdmin()) {
            return true;
        }
        return $this->objPermissions->checkAclByNameFresh($capability);
    }

    /** Provision the default role bundle without granting it to any user. */
    public function ensureDefaultRole()
    {
        if (!$this->objUser->isAdmin()) {
            return $this->result(false, 'administrator_required');
        }
        $groups = $this->objGroups->ensureGroups(array(array(
            'name' => self::ROLE_NAME,
            'description' => 'Operational membership management role',
        )));
        if (empty($groups['ok']) || empty($groups['groups'][self::ROLE_NAME])) {
            return $this->result(false, 'role_group_failed');
        }
        $groupId = (int) $groups['groups'][self::ROLE_NAME];
        foreach (self::CAPABILITIES as $name => $description) {
            if (!$this->ensureCapability($name, $description)) {
                return $this->result(false, 'capability_failed');
            }
        }
        foreach (self::DEFAULT_ROLE_CAPABILITIES as $name) {
            if (!$this->ensureGroupCapability($groupId, $name)) {
                return $this->result(false, 'role_capability_failed');
            }
        }
        return $this->result(true, 'role_ready', $groupId);
    }

    /** Assign the role bundle; only a site administrator may assign operators. */
    public function assignDefaultRole($userId, $correlationId)
    {
        if (!$this->objUser->isAdmin()) {
            return $this->result(false, 'administrator_required');
        }
        $ready = $this->ensureDefaultRole();
        $userId = $this->userId($userId);
        $correlationId = $this->identifier($correlationId, 64);
        if (empty($ready['ok']) || $userId === null || $correlationId === null) {
            return $this->result(false, 'invalid_role_assignment');
        }
        $permissionUserId = $this->objIdentity->permissionUserIdForUser($userId);
        if ($permissionUserId === null
            || !$this->objGroups->ensureMembership($ready['groupId'], $permissionUserId)) {
            return $this->result(false, 'role_assignment_failed');
        }
        return $this->auditRoleChange(
            'membership.role_assigned',
            $userId,
            $correlationId,
            $ready['groupId']
        );
    }

    public function removeDefaultRole($userId, $correlationId)
    {
        if (!$this->objUser->isAdmin()) {
            return $this->result(false, 'administrator_required');
        }
        $userId = $this->userId($userId);
        $correlationId = $this->identifier($correlationId, 64);
        $groupId = $this->objGroups->groupIdForName(self::ROLE_NAME);
        $permissionUserId = $userId === null ? null
            : $this->objIdentity->permissionUserIdForUser($userId);
        if ($userId === null || $correlationId === null || !$groupId
            || $permissionUserId === null
            || !$this->objGroups->removeMembership($groupId, $permissionUserId)) {
            return $this->result(false, 'role_removal_failed');
        }
        return $this->auditRoleChange(
            'membership.role_removed',
            $userId,
            $correlationId,
            (int) $groupId
        );
    }

    private function ensureCapability($name, $description)
    {
        $id = $this->objPermissions->getId($name, 'name');
        if ($id) {
            return true;
        }
        return $this->objPermissions->newAcl($name, $description) !== false;
    }

    private function ensureGroupCapability($groupId, $name)
    {
        $aclId = $this->objPermissions->getId($name, 'name');
        if (!$aclId) {
            return false;
        }
        $rows = $this->getArray(
            'SELECT COUNT(*) AS cnt FROM tbl_permissions_acl'
            . ' WHERE acl_id = ' . $this->quote($aclId)
            . ' AND group_id = ' . (int) $groupId
        );
        if (is_array($rows) && isset($rows[0]['cnt'])
            && (int) $rows[0]['cnt'] > 0) {
            return true;
        }
        return $this->objPermissions->addAclGroup($aclId, $groupId) !== false;
    }

    private function auditRoleChange($type, $userId, $correlationId, $groupId)
    {
        $event = $this->objEvents->append(array(
            'eventType' => $type,
            'subjectType' => 'user',
            'subjectId' => $userId,
            'actorType' => 'user',
            'actorId' => (string) $this->objUser->userId(),
            'outcome' => 'succeeded',
            'correlationId' => $correlationId,
            'sourceService' => 'membership-service',
            'metadata' => array('role' => self::ROLE_NAME, 'group_id' => $groupId),
        ));
        return !empty($event['ok'])
            ? $this->result(true, $type, $groupId)
            : $this->result(false, 'role_audit_failed');
    }

    private function capability($value)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return array_key_exists($value, self::CAPABILITIES) ? $value : null;
    }

    private function userId($value)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value !== '' && strlen($value) <= 25
            && !preg_match('/[\x00-\x1F\x7F]/', $value) ? $value : null;
    }

    private function identifier($value, $maximum)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value !== '' && strlen($value) <= $maximum
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/', $value)
            ? $value : null;
    }

    private function quote($value)
    {
        $database = $this->objEngine->getDbObj();
        return method_exists($database, 'quoteSmart')
            ? $database->quoteSmart((string) $value)
            : "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function result($ok, $code, $groupId = null)
    {
        return array(
            'ok' => (bool) $ok,
            'code' => $code,
            'groupId' => $groupId,
        );
    }
}
?>
