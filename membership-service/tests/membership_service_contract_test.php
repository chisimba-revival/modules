<?php
$root = dirname(__DIR__);
$service = file_get_contents($root . '/classes/membershipservice_class_inc.php');
$authorization = file_get_contents($root . '/classes/membershipauthorizationservice_class_inc.php');
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/manage_tpl.php');
$layout = file_get_contents($root . '/templates/layout/membership_layout.php');
$periods = file_get_contents($root . '/sql/tbl_membership_service_periods.sql');
$updates = file_get_contents($root . '/sql/sql_updates.xml');
$backfill = file_get_contents(
    $root . '/sql/migrations/20260825_backfill_current_entitlement_key.sql'
);
$registration = file_get_contents($root . '/register.conf');
$checks = array(
    'service identity' => str_contains($registration, 'MODULE_ID: membership-service'),
    'ordered tiers' => str_contains($service, "'free' => 0")
        && str_contains($service, "'tier_1' => 1")
        && str_contains($service, "'tier_2' => 2"),
    'tier inheritance' => str_contains($service, 'tierIncludes('),
    'private is not a tier' => !str_contains($service, "'private' =>"),
    'period lifecycle' => str_contains($service, "'scheduled' => array('active', 'expired')")
        && str_contains($service, "'grace' => array('active', 'expired')"),
    'provider neutral' => !preg_match('/yoco|paypal/i', $service . $periods),
    'idempotent creation' => str_contains($periods, "'unique' => TRUE")
        && str_contains($service, 'already_created'),
    'current entitlement upgrade is additive and backfilled' => str_contains(
        $updates,
        '<name>current_entitlement_key</name>'
    ) && str_contains($backfill, 'UPDATE tbl_membership_service_periods')
        && str_contains($backfill, "':amend:%'"),
    'entitlement integration' => str_contains($service, "'entitlementType' => 'membership_tier'"),
    'effective tier comes from entitlements' => str_contains($service, 'activeForUser(')
        && str_contains($service, "'membership_tier'"),
    'grace has explicit grant' => str_contains($service, "'membership_grace'")
        && str_contains($service, "':grace'"),
    'grace recovery and expiry revoke grants' => str_contains(
        $service,
        "\$previousState === 'grace' && \$nextState === 'active'"
    ) && str_contains($service, "'membership_grace_ended'")
        && str_contains($service, "'membership_period_ended'"),
    'membership owns composed entitlement transaction' => str_contains(
        $service,
        '), false);'
    ) && substr_count($service, "\$correlationId,\n            false") >= 2,
    'audited transitions' => str_contains($service, 'membership.period_created')
        && str_contains($service, 'membership.period_'),
    'dbTable update contract' => str_contains(
        $service,
        "\$this->update(\n            'id',\n            \$periodId,\n            array("
    ),
    'database errors fail without rejecting successful result objects' => str_contains(
        $service,
        'mutationSucceeded($updated)'
    ) && str_contains($service, "PEAR::isError(\$result)")
        && str_contains($service, "is_a(\$result, 'PEAR_Error')")
        && !str_contains($service, 'return $result !== false && !is_object($result);'),
    'canonical user validation' => str_contains($service, 'findByUserId('),
    'bounded period times' => str_contains($service, '$endsAt <= $startsAt'),
    'audited membership amendments replace entitlement' => str_contains(
        $service,
        'public function amendPeriod('
    ) && str_contains($service, "'membership_period_amended'")
        && str_contains($service, "'membership.period_amended'"),
    'no role or course groups' => !str_contains($service, 'contextgroups')
        && !str_contains($service, 'security_role'),
    'capability not role checks' => str_contains($authorization, 'public function can(')
        && str_contains($authorization, "'membership.view'")
        && str_contains($authorization, "'membership.manage'")
        && !str_contains($authorization, "isGroupMember(\$this->objUser->userId(), self::ROLE_NAME"),
    'shared ACL reader authorizes capabilities' => str_contains(
        $authorization,
        'objPermissions->checkAclByNameFresh($capability)'
    ) && !str_contains($authorization, 'INNER JOIN tbl_perms_groupusers'),
    'administrator inheritance' => str_contains($authorization, 'objUser->isAdmin()'),
    'membership manager bundle excludes refunds' => str_contains($authorization, "'payment.refund'")
        && !preg_match("/DEFAULT_ROLE_CAPABILITIES[^;]+payment\\.refund/s", $authorization),
    'role assignment is audited' => str_contains($authorization, "'membership.role_assigned'")
        && str_contains($authorization, "'membership.role_removed'"),
    'manual membership requires both capabilities' => str_contains(
        $controller,
        "authorization->can('membership.manage')"
    ) && str_contains($controller, "authorization->can('membership.override')"),
    'membership mutations require csrf post' => str_contains($controller, 'validPost()')
        && str_contains($controller, "case 'createperiod'")
        && str_contains($controller, "case 'transition'"),
    'operator assignment remains administrator only' => str_contains(
        $controller,
        "!\$this->validPost() || !\$this->user->isAdmin()"
    ),
    'workbench exposes lifecycle and operator boundaries' => str_contains(
        $template,
        'Add a manual membership'
    ) && str_contains($template, 'Membership Managers')
        && str_contains($template, 'End membership'),
    'workbench edits active membership explicitly' => str_contains(
        $template,
        'Save membership changes'
    ) && str_contains($controller, "case 'editperiod'"),
    'standard wide and narrow administration layout' => str_contains(
        $controller,
        "setLayoutTemplate('membership_layout.php')"
    ) && str_contains($layout, 'setNumColumns(2)')
        && str_contains($layout, "getObject('postloginmenu', 'toolbar')->show()")
        && str_contains($layout, 'chisimba-structural-sidebar membership-admin-sidebar')
        && str_contains($layout, 'chisimba-structural-main membership-wide-column'),
    'operator forms use canonical normalized identity' => str_contains(
        $template,
        "\$person['displayName']"
    ) && str_contains($template, "\$person['userId']"),
    'grace language states the operational outcome' => str_contains(
        $template,
        'Allow grace access'
    ) && str_contains($template, 'Restore active status')
        && !str_contains($template, '>Reinstate<'),
    'expired periods are labelled as audit history' => str_contains(
        $template,
        "'auditrecord', 'Audit record'"
    ) && str_contains($template, 'membership-audit-record')
        && str_contains($template, "'membership-service'"),
    'action controls do not break table-cell geometry' => str_contains(
        $template,
        '<td class="membership-actions"><div class="membership-action-list">'
    ) && str_contains($template, '.membership-action-list{display:flex')
        && !str_contains($template, '.membership-actions{display:flex'),
    'workbench cards use the shared wide-card primitive' => substr_count(
        $template,
        'chisimba-form-card chisimba-form-card--wide'
    ) === 3,
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " membership service contract checks\n";
?>
