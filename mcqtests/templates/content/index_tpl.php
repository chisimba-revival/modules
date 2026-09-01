<?php
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$u = fn($params, $module = 'mcqtests') => html_entity_decode(
    $this->uri($params, $module),
    ENT_QUOTES | ENT_HTML5,
    'UTF-8'
);
$t = fn($key, $fallback) => $this->objLanguage->languageText($key, 'mcqtests', $fallback);
$icons = $this->getObject('iconservice', 'ui');
$iconBase = $this->getResourceUri('icons/lucide/', 'ui');
$canManage = $this->isValid('add');
$formatClosing = function ($value) use ($t) {
    $value = trim((string) $value);
    if ($value === '' || str_starts_with($value, '0000-00-00') || $value === '-2') {
        return $t('mod_mcqtests_no_closing_date', 'No closing date');
    }
    return $this->formatDate($value);
};
$iconLink = function ($icon, $label, $url, $extra = '') use ($e, $iconBase) {
    return '<a class="chisimba-icon-button" href="' . $e($url) . '" title="' . $e($label)
        . '" aria-label="' . $e($label) . '" ' . $extra . '><img src="'
        . $e($iconBase . $icon . '.svg') . '" width="18" height="18" alt="" aria-hidden="true" /></a>';
};
$tests = is_array($data ?? null) ? $data : array();
$updateUrl = $u(array('action' => 'updateoverview'));
?>
<section class="chisimba-card mcq-overview" data-update-url="<?php echo $e($updateUrl); ?>">
    <div class="mcq-overview__heading">
        <div>
            <h1><?php echo $e($t('mod_mcqtests_onlinetests', 'Online Tests')); ?></h1>
            <p><?php echo $e($t('mod_mcqtests_overview_help', 'Manage test availability and marks. Course weighting is managed in Gradebook.')); ?></p>
        </div>
        <?php if ($canManage): ?>
            <div class="chisimba-form-actions mcq-overview__create-actions">
                <a class="button" href="<?php echo $e($u(array('action' => 'addstep'))); ?>">
                    <?php echo $icons->render('circle-plus', array('decorative'=>true)); ?>
                    <?php echo $e($t('mod_mcqtests_addtest', 'Create test')); ?>
                </a>
                <?php if (!empty($aiAvailable) && !empty($chapterQuizMissingCount)): ?>
                    <a class="button" href="<?php echo $e($u(array('action' => 'aichapterquizzes'))); ?>">
                        <?php echo $icons->render('sparkles', array('decorative'=>true)); ?>
                        <?php echo $e(sprintf($t('mod_mcqtests_ai_missing_chapter_button', 'Generate missing chapter quizzes (%d)'), $chapterQuizMissingCount)); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($canManage && !empty($tests)): ?>
        <div class="mcq-overview__bulk chisimba-card" aria-label="<?php echo $e($t('mod_mcqtests_bulk_actions', 'Bulk actions')); ?>">
            <button class="button" type="button" data-operation="activate_all"><?php echo $e($t('mod_mcqtests_activate_all', 'Mark all active')); ?></button>
            <label>
                <span><?php echo $e($t('mod_mcqtests_same_marks', 'Assign all the same marks')); ?></span>
                <input type="number" min="1" max="10000" step="1" value="5" data-bulk-marks />
            </label>
            <button class="button chisimba-button-secondary" type="button" data-operation="marks_all"><?php echo $e($t('mod_mcqtests_apply_marks', 'Apply marks')); ?></button>
            <span class="mcq-overview__save-status" role="status" aria-live="polite"></span>
        </div>
    <?php endif; ?>

    <input type="hidden" value="<?php echo $e($overviewToken); ?>" data-overview-csrf />
    <div class="chisimba-table-scroll">
        <table class="mcq-test-list">
            <thead><tr>
                <th><?php echo $e($t('mod_mcqtests_wordname', 'Name')); ?></th>
                <th><?php echo $e($t('mod_mcqtests_chapter', 'Chapter')); ?></th>
                <th><?php echo $e($t('mod_mcqtests_status', 'Status')); ?></th>
                <th><?php echo $e($t('mod_mcqtests_marks', 'Marks')); ?></th>
                <th><?php echo $e($t('mod_mcqtests_closingdate', 'Closing date and time')); ?></th>
                <th><span class="visually-hidden"><?php echo $e($t('mod_mcqtests_actions', 'Actions')); ?></span></th>
            </tr></thead>
            <tbody>
            <?php if (empty($tests)): ?>
                <tr><td colspan="6" class="noRecordsMessage"><?php echo $e($t('mod_mcqtests_notests', 'There are no tests yet.')); ?></td></tr>
            <?php else: ?>
                <?php foreach ($tests as $test):
                    $id = (string) $test['id'];
                    $name = (string) $test['name'];
                    $status = (string) $test['status'];
                    $statusLabel = $status === 'open'
                        ? $t('mod_mcqtests_active_label', 'Active')
                        : $t('mod_mcqtests_inactive_label', 'Not active');
                ?>
                    <tr data-test-id="<?php echo $e($id); ?>">
                        <td><a href="<?php echo $e($u(array('action' => 'view', 'id' => $id))); ?>"><?php echo $e($name); ?></a></td>
                        <td><?php echo $e($test['node'] ?: $t('mod_mcqtests_unassigned_chapter', 'Not assigned')); ?></td>
                        <td><span class="chisimba-pill <?php echo $status === 'open' ? 'chisimba-pill--success' : ''; ?>" data-test-status><?php echo $e($statusLabel); ?></span></td>
                        <td>
                            <?php if ($canManage): ?>
                                <input class="mcq-inline-mark" type="number" min="<?php echo $e(max(1, (int) $test['questioncount'])); ?>" max="10000" step="1" value="<?php echo $e($test['actualmarks']); ?>" data-test-marks aria-label="<?php echo $e(sprintf($t('mod_mcqtests_marks_for', 'Marks for %s'), $name)); ?>" />
                            <?php else: ?><?php echo $e($test['actualmarks']); ?><?php endif; ?>
                        </td>
                        <td><?php echo $e($formatClosing($test['closingdate'] ?? '')); ?></td>
                        <td><span class="mcq-action-group">
                            <?php if ($canManage):
                                echo $iconLink('pencil', sprintf($t('mod_mcqtests_edit_named', 'Edit %s'), $name), $u(array('action' => 'edit', 'id' => $id)));
                                echo $iconLink('trash-2', sprintf($t('mod_mcqtests_delete_named', 'Delete %s'), $name), $u(array('action' => 'delete', 'id' => $id)), 'onclick="return confirm(' . $e(json_encode($t('mod_mcqtests_delete_confirm', 'Delete this test?'))) . ')"');
                                echo $iconLink('list-checks', sprintf($t('mod_mcqtests_results_named', 'Show results for %s'), $name), $u(array('action' => 'liststudents', 'id' => $id)));
                                echo $iconLink('download', sprintf($t('mod_mcqtests_export_named', 'Export results for %s'), $name), $u(array('action' => 'doexport', 'testId' => $id)));
                            endif; ?>
                        </span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="chisimba-form-actions">
        <a class="button chisimba-button-secondary" href="<?php echo $e($u(array('action' => 'assessmentSheet'), 'gradebook')); ?>"><?php echo $e($t('mod_mcqtests_assessmentsheet', 'Assessment Sheet')); ?></a>
    </div>
</section>
<script>
(function () {
    const root = document.querySelector('.mcq-overview');
    if (!root) return;
    const token = root.querySelector('[data-overview-csrf]');
    const status = root.querySelector('.mcq-overview__save-status');
    const post = async (values) => {
        const body = new URLSearchParams(Object.assign({csrf_token: token.value}, values));
        const response = await fetch(root.dataset.updateUrl, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: body.toString(), credentials: 'same-origin'});
        const result = await response.json();
        if (result.csrfToken) token.value = result.csrfToken;
        if (!response.ok || !result.ok) throw new Error(result.error || 'save_failed');
        return result;
    };
    const announce = (message, failed) => { if (status) { status.textContent = message; status.classList.toggle('error', !!failed); } };
    root.querySelectorAll('[data-test-marks]').forEach((input) => {
        input.addEventListener('change', async () => {
            input.disabled = true;
            try { await post({operation: 'mark_one', test_id: input.closest('tr').dataset.testId, marks: input.value}); announce('<?php echo $e($t('mod_mcqtests_marks_saved', 'Marks saved.')); ?>', false); }
            catch (error) { announce('<?php echo $e($t('mod_mcqtests_marks_not_saved', 'Marks could not be changed. Tests with attempts are protected.')); ?>', true); }
            finally { input.disabled = false; }
        });
    });
    root.querySelectorAll('[data-operation]').forEach((button) => button.addEventListener('click', async () => {
        button.disabled = true;
        const operation = button.dataset.operation;
        const marks = root.querySelector('[data-bulk-marks]');
        try {
            const result = await post({operation: operation, marks: marks ? marks.value : ''});
            if (operation === 'activate_all') result.updated.forEach((id) => { const badge = root.querySelector('tr[data-test-id="' + CSS.escape(id) + '"] [data-test-status]'); if (badge) { badge.textContent = '<?php echo $e($t('mod_mcqtests_active_label', 'Active')); ?>'; badge.classList.add('chisimba-pill--success'); } });
            if (operation === 'marks_all') result.updated.forEach((id) => { const field = root.querySelector('tr[data-test-id="' + CSS.escape(id) + '"] [data-test-marks]'); if (field) field.value = marks.value; });
            announce('<?php echo $e($t('mod_mcqtests_changes_saved', 'Changes saved.')); ?>', false);
        } catch (error) { announce('<?php echo $e($t('mod_mcqtests_changes_not_saved', 'The changes could not be applied.')); ?>', true); }
        finally { button.disabled = false; }
    }));
}());
</script>
