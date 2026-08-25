<?php
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$language = $this->getObject('language', 'language');
$text = static function ($key, $fallback) use ($language) {
    return $language->languageText(
        'mod_membership_service_' . $key,
        'membership-service',
        $fallback
    );
};
$url = function (array $params) {
    return htmlspecialchars(
        html_entity_decode($this->uri($params), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ENT_QUOTES,
        'UTF-8'
    );
};
$today = date('Y-m-d');
$nextYear = date('Y-m-d', strtotime('+1 year'));
$editing = is_array($membershipEditPeriod);
$formStart = $editing ? substr($membershipEditPeriod['starts_at'], 0, 10) : $today;
$formEnd = $editing ? substr($membershipEditPeriod['ends_at'], 0, 10) : $nextYear;
$formTier = $editing ? $membershipEditPeriod['tier_code'] : 'tier_1';
$formReason = $editing ? (string) $membershipEditPeriod['source_reference'] : '';
?>
<main class="chisimba-workspace membership-workspace">
    <header class="membership-header">
        <div>
            <p class="membership-eyebrow">Membership operations</p>
            <h1>Memberships</h1>
            <p>Manage tier access and audited manual overrides without changing course roles.</p>
        </div>
        <span class="membership-count"><?php echo count($membershipPeriods); ?> periods</span>
    </header>

    <?php if ($membershipMessage !== ''): ?>
        <p class="chisimba-notice chisimba-notice--success chisimba-notice--transient" role="status">
            <?php echo $escape($membershipMessage === 'membership_created' ? 'Membership created.' : ($membershipMessage === 'membership_amended' ? 'Membership changes saved.' : ($membershipMessage === 'membership_updated' ? 'Membership updated.' : 'Role assignment updated.'))); ?>
        </p>
    <?php endif; ?>
    <?php if ($membershipError !== ''): ?>
        <p class="chisimba-notice chisimba-notice--error" role="alert">The change could not be completed: <?php echo $escape(str_replace('_', ' ', $membershipError)); ?>.</p>
    <?php endif; ?>

    <?php if ($membershipCanManage && $membershipCanOverride): ?>
    <section class="chisimba-form-card chisimba-form-card--wide membership-create" aria-labelledby="membership-create-title">
        <div>
            <h2 id="membership-create-title"><?php echo $editing ? 'Edit membership' : 'Add a manual membership'; ?></h2>
            <p><?php echo $editing ? 'Saving creates an audited amendment and replaces the previous entitlement grant.' : 'Use this for an approved exception, offline payment or invited member. The reason is retained in the audit trail.'; ?></p>
        </div>
        <form method="post" action="<?php echo $url(array('action' => $editing ? 'editperiod' : 'createperiod')); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $escape($membershipCsrf); ?>">
            <?php if ($editing): ?><input type="hidden" name="period_id" value="<?php echo $escape($membershipEditPeriod['id']); ?>"><?php endif; ?>
            <label class="chisimba-form-field">Member
                <select name="user_id" required <?php echo $editing ? 'disabled' : ''; ?>>
                    <option value="">Choose a user…</option>
                    <?php foreach ($membershipUsers as $person): ?>
                        <option value="<?php echo $escape($person['userid']); ?>" <?php echo $editing && (string) $person['userid'] === (string) $membershipEditPeriod['user_id'] ? 'selected' : ''; ?>><?php echo $escape(trim($person['firstname'] . ' ' . $person['surname']) . ' (' . $person['username'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="chisimba-form-field">Membership tier
                <select name="tier" required><option value="tier_1" <?php echo $formTier === 'tier_1' ? 'selected' : ''; ?>>Tier 1</option><option value="tier_2" <?php echo $formTier === 'tier_2' ? 'selected' : ''; ?>>Tier 2</option></select>
            </label>
            <label class="chisimba-form-field">Starts on<input type="date" name="starts_at" value="<?php echo $escape($formStart); ?>" required></label>
            <label class="chisimba-form-field">Ends on<input type="date" name="ends_at" value="<?php echo $escape($formEnd); ?>" required></label>
            <label class="chisimba-form-field membership-reason">Reason for the override<input type="text" name="reason" maxlength="191" value="<?php echo $escape($formReason); ?>" required placeholder="For example: invited founding member"></label>
            <div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $editing ? 'Save membership changes' : 'Add membership'; ?></button><?php if ($editing): ?> <a class="button chisimba-button-secondary" href="<?php echo $url(array()); ?>">Cancel</a><?php endif; ?></div>
        </form>
    </section>
    <?php endif; ?>

    <section class="chisimba-form-card chisimba-form-card--wide membership-list" aria-labelledby="membership-list-title">
        <div class="membership-section-heading"><div><h2 id="membership-list-title">Membership history</h2><p>Current and previous membership periods, most recently changed first.</p></div></div>
        <?php if (empty($membershipPeriods)): ?>
            <p class="membership-empty">No membership periods have been created yet.</p>
        <?php else: ?>
        <div class="membership-table-wrap"><table class="membership-table">
            <thead><tr><th>Member</th><th>Tier</th><th>Status</th><th>Period</th><th>Source</th><?php if ($membershipCanManage): ?><th><span class="visually-hidden">Actions</span></th><?php endif; ?></tr></thead>
            <tbody><?php foreach ($membershipPeriods as $period): ?>
                <tr>
                    <td><strong><?php echo $escape($period['person']); ?></strong><?php if ($period['username'] !== ''): ?><small><?php echo $escape($period['username']); ?></small><?php endif; ?></td>
                    <td><?php echo $escape(str_replace('_', ' ', strtoupper($period['tier_code']))); ?></td>
                    <td><span class="membership-status membership-status--<?php echo $escape($period['state']); ?>"><?php echo $escape(ucfirst($period['state'])); ?></span><?php if ($period['state'] === 'grace' && !empty($period['grace_ends_at'])): ?><small>Temporary access until <?php echo $escape(date('j M Y', strtotime($period['grace_ends_at']))); ?></small><?php endif; ?></td>
                    <td><time><?php echo $escape(date('j M Y', strtotime($period['starts_at']))); ?></time><span> to </span><time><?php echo $escape(date('j M Y', strtotime($period['ends_at']))); ?></time></td>
                    <td><?php echo $escape(str_replace('_', ' ', $period['source_type'])); ?></td>
                    <?php if ($membershipCanManage): ?><td class="membership-actions"><div class="membership-action-list">
                        <?php if (in_array($period['state'], array('active', 'scheduled'), true)): ?><a class="button chisimba-button-secondary" href="<?php echo $url(array('edit' => $period['id'])); ?>">Edit</a><?php endif; ?>
                        <?php if ($period['state'] === 'scheduled'): ?>
                            <form method="post" action="<?php echo $url(array('action' => 'transition')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($membershipCsrf); ?>"><input type="hidden" name="period_id" value="<?php echo $escape($period['id']); ?>"><input type="hidden" name="next_state" value="active"><button class="chisimba-button-secondary" type="submit">Activate</button></form>
                        <?php endif; ?>
                        <?php if ($period['state'] === 'active' && strtotime($period['ends_at']) <= time()): ?>
                            <form class="membership-grace-form" method="post" action="<?php echo $url(array('action' => 'transition')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($membershipCsrf); ?>"><input type="hidden" name="period_id" value="<?php echo $escape($period['id']); ?>"><input type="hidden" name="next_state" value="grace"><label>Temporary access ends<span class="visually-hidden"> for <?php echo $escape($period['person']); ?></span><input type="date" name="grace_ends_at" min="<?php echo $escape(date('Y-m-d', strtotime($period['ends_at'] . ' +1 day'))); ?>" value="<?php echo $escape(date('Y-m-d', strtotime($period['ends_at'] . ' +7 days'))); ?>" required></label><button class="chisimba-button-secondary" type="submit">Allow grace access</button></form>
                        <?php elseif ($period['state'] === 'grace'): ?>
                            <form method="post" action="<?php echo $url(array('action' => 'transition')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($membershipCsrf); ?>"><input type="hidden" name="period_id" value="<?php echo $escape($period['id']); ?>"><input type="hidden" name="next_state" value="active"><button class="chisimba-button-secondary" type="submit">Restore active status</button></form>
                        <?php endif; ?>
                        <?php if (in_array($period['state'], array('active', 'grace', 'scheduled'), true)): ?>
                            <form method="post" action="<?php echo $url(array('action' => 'transition')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($membershipCsrf); ?>"><input type="hidden" name="period_id" value="<?php echo $escape($period['id']); ?>"><input type="hidden" name="next_state" value="expired"><button class="chisimba-button-danger" type="submit">End membership</button></form>
                        <?php else: ?>
                            <span class="membership-audit-record"><?php echo $escape($text('auditrecord', 'Audit record')); ?></span>
                        <?php endif; ?>
                    </div></td><?php endif; ?>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
    </section>

    <?php if ($membershipIsAdmin): ?>
    <section class="chisimba-form-card chisimba-form-card--wide membership-operators" aria-labelledby="membership-operators-title">
        <div><h2 id="membership-operators-title">Membership Managers</h2><p>Membership Managers can operate memberships and private admission. Only site administrators can appoint or remove them.</p></div>
        <div class="membership-operator-grid">
            <div><h3>Current managers</h3><?php if (empty($membershipRoleMembers)): ?><p>None assigned.</p><?php else: ?><ul><?php foreach ($membershipRoleMembers as $person): ?><li><span><?php echo $escape($person['displayName'] . ' (' . $person['username'] . ')'); ?></span><form method="post" action="<?php echo $url(array('action' => 'removerole')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($membershipCsrf); ?>"><input type="hidden" name="user_id" value="<?php echo $escape($person['userId']); ?>"><button class="chisimba-button-danger" type="submit">Remove</button></form></li><?php endforeach; ?></ul><?php endif; ?></div>
            <div><h3>Assign a manager</h3><form method="post" action="<?php echo $url(array('action' => 'assignrole')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($membershipCsrf); ?>"><label class="chisimba-form-field">User<select name="user_id" required><option value="">Choose a user…</option><?php foreach ($membershipRoleCandidates as $person): ?><option value="<?php echo $escape($person['userId']); ?>"><?php echo $escape($person['displayName'] . ' (' . $person['username'] . ')'); ?></option><?php endforeach; ?></select></label><button class="button" type="submit">Assign Membership Manager</button></form></div>
        </div>
    </section>
    <?php endif; ?>
</main>

<style>
.membership-workspace{max-width:86rem;margin:0 auto;display:grid;gap:1.5rem}.membership-header,.membership-section-heading{display:flex;align-items:start;justify-content:space-between;gap:1rem}.membership-header h1,.membership-list h2,.membership-create h2,.membership-operators h2{margin-top:0}.membership-header p,.membership-section-heading p,.membership-create>div p,.membership-operators>div p{color:var(--chisimba-text-muted)}.membership-eyebrow{margin:0;color:var(--chisimba-primary)!important;font-size:.78rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}.membership-count{padding:.4rem .75rem;border-radius:999px;background:var(--chisimba-surface-subtle);font-weight:700}.membership-create{display:grid;grid-template-columns:minmax(15rem,.55fr) minmax(0,1.45fr);gap:2rem;align-items:start}.membership-create form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.membership-create label,.membership-operators label{display:grid;gap:.35rem;font-weight:600}.membership-reason,.membership-create .chisimba-form-actions{grid-column:1/-1}.membership-table-wrap{overflow-x:auto}.membership-table{width:100%;border-collapse:collapse}.membership-table th,.membership-table td{padding:.8rem;text-align:left;vertical-align:middle;border-bottom:1px solid var(--chisimba-border)}.membership-table td small{display:block;color:var(--chisimba-text-muted)}.membership-status{display:inline-flex;padding:.25rem .55rem;border-radius:999px;background:var(--chisimba-surface-subtle);font-weight:700}.membership-status--active{color:var(--chisimba-success-text);background:var(--chisimba-success-surface)}.membership-status--expired{color:var(--chisimba-text-muted)}.membership-status--grace{color:var(--chisimba-warning-text);background:var(--chisimba-warning-surface)}.membership-action-list{display:flex;flex-wrap:wrap;gap:.5rem;justify-content:flex-end}.membership-actions form{margin:0}.membership-audit-record{color:var(--chisimba-text-muted);font-size:.9rem;white-space:nowrap}.membership-grace-form{display:flex!important;align-items:end;gap:.4rem}.membership-grace-form label{display:grid;gap:.2rem;font-size:.8rem}.membership-grace-form input{max-width:10rem}.membership-operator-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:2rem}.membership-operator-grid h3{margin-top:0}.membership-operator-grid ul{list-style:none;margin:0;padding:0;display:grid;gap:.5rem}.membership-operator-grid li{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.6rem;border-bottom:1px solid var(--chisimba-border)}.membership-operator-grid form{display:grid;gap:.75rem}.membership-empty{color:var(--chisimba-text-muted)}@media(max-width:60rem){.membership-create,.membership-operator-grid{grid-template-columns:1fr}}@media(max-width:40rem){.membership-create form{grid-template-columns:1fr}.membership-reason,.membership-create .chisimba-form-actions{grid-column:auto}.membership-header{align-items:stretch;flex-direction:column}.membership-count{align-self:flex-start}}
</style>
