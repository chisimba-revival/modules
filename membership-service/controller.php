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
            default: return $this->home();
        }
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
