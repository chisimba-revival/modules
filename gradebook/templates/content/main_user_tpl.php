<?php
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}
$this->setLayoutTemplate('gradebook_layout_tpl.php');
$studentUserId = $this->objUser->userId();
$contextObject = $this->getObject('dbcontext', 'context');
$contextCode = $contextObject->getContextCode();
$dateTime = $this->getObject('dateandtime', 'utilities');
$rows = $this->studentAssessmentRows($studentUserId);
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$number = function ($value) {
    return number_format((float) $value, 2, '.', '');
};
$statusLabels = array(
    'not_attempted' => $objLanguage->languageText('mod_gradebook_result_notattempted', 'gradebook'),
    'in_progress' => $objLanguage->languageText('mod_gradebook_result_inprogress', 'gradebook'),
    'submitted' => $objLanguage->languageText('mod_gradebook_result_submitted', 'gradebook'),
    'marked' => $objLanguage->languageText('mod_gradebook_result_marked', 'gradebook')
);
$plannedContribution = 0.0;
$markedContribution = 0.0;
$currentCourseMark = 0.0;
$markedCount = 0;
foreach ($rows as $row) {
    $plannedContribution += $row['weight'];
    if ($row['mark_percent'] !== null) {
        $markedContribution += $row['weight'];
        $currentCourseMark += $row['weighted_mark'];
        $markedCount++;
    }
}
?>
<style>
.student-gradebook { color: #14213d; }
.student-gradebook__identity { margin: .25rem 0 1.25rem; color: #405064; }
.student-gradebook__table-wrap { overflow-x: auto; border: 1px solid #d8e0e8; border-radius: 12px; }
.student-gradebook__table { width: 100%; border-collapse: collapse; background: #fff; }
.student-gradebook__table th { padding: .8rem .75rem; text-align: left; font-size: .88rem; color: #405064; background: #f5f8fa; border-bottom: 1px solid #d8e0e8; }
.student-gradebook__table td { padding: .9rem .75rem; vertical-align: top; border-bottom: 1px solid #e5eaef; }
.student-gradebook__table tr:last-child td { border-bottom: 0; }
.student-gradebook__title { font-weight: 700; color: #14213d; }
.student-gradebook__muted { color: #687789; }
.student-gradebook__number { white-space: nowrap; font-variant-numeric: tabular-nums; }
.student-gradebook__status { display: inline-flex; padding: .28rem .58rem; border-radius: 999px; font-size: .82rem; font-weight: 700; background: #eef2f5; color: #435466; white-space: nowrap; }
.student-gradebook__status--marked { background: #e6f5ea; color: #17633a; }
.student-gradebook__status--submitted { background: #fff3d6; color: #7a5300; }
.student-gradebook__summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin: 1.25rem 0; }
.student-gradebook__summary-card { padding: 1rem; border: 1px solid #d8e0e8; border-radius: 12px; background: #f8fafb; }
.student-gradebook__summary-label { display: block; color: #526377; font-size: .86rem; margin-bottom: .3rem; }
.student-gradebook__summary-value { display: block; font-size: 1.25rem; font-weight: 800; color: #245b57; }
.student-gradebook__empty { padding: 1.25rem; border: 1px solid #d8e0e8; border-radius: 12px; background: #f8fafb; color: #526377; }
@media (max-width: 760px) { .student-gradebook__summary { grid-template-columns: 1fr; } }
</style>
<div class="student-gradebook">
    <h2><?php echo $escape(($contextCode ? $contextObject->getMenuText($contextCode) : '') . ' ' . $objLanguage->languageText('mod_gradebook_title', 'gradebook') . ' - ' . $this->objUser->fullname($studentUserId)); ?></h2>
    <p class="student-gradebook__identity"><strong><?php echo $escape($objLanguage->languageText('mod_gradebook_studentNumber', 'gradebook')); ?>:</strong> <?php echo $escape($this->objUser->username($studentUserId)); ?></p>

    <?php if (empty($rows)): ?>
        <div class="student-gradebook__empty"><?php echo $escape($objLanguage->languageText('mod_gradebook_noassessmentplanstudent', 'gradebook')); ?></div>
    <?php else: ?>
        <div class="student-gradebook__table-wrap">
            <table class="student-gradebook__table">
                <thead><tr>
                    <th><?php echo $escape($objLanguage->languageText('mod_gradebook_assessment', 'gradebook')); ?></th>
                    <th><?php echo $escape($objLanguage->languageText('mod_gradebook_assessmentType', 'gradebook')); ?></th>
                    <th><?php echo $escape($objLanguage->languageText('mod_gradebook_closingDate', 'gradebook')); ?></th>
                    <th><?php echo $escape($objLanguage->languageText('mod_gradebook_contribution', 'gradebook')); ?></th>
                    <th><?php echo $escape($objLanguage->languageText('mod_gradebook_activitystatus', 'gradebook')); ?></th>
                    <th><?php echo $escape($objLanguage->languageText('mod_gradebook_mark', 'gradebook')); ?></th>
                    <th><?php echo $escape($objLanguage->languageText('mod_gradebook_coursemarkearned', 'gradebook')); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $status = isset($statusLabels[$row['status']]) ? $statusLabels[$row['status']] : $statusLabels['not_attempted'];
                    $statusClass = in_array($row['status'], array('marked', 'submitted'), true) ? ' student-gradebook__status--' . $row['status'] : '';
                ?>
                    <tr>
                        <td><span class="student-gradebook__title"><?php echo $escape($row['title']); ?></span></td>
                        <td><?php echo $escape($row['provider']); ?></td>
                        <td><?php echo $row['closing_date'] === null ? '<span class="student-gradebook__muted">' . $escape($objLanguage->languageText('mod_gradebook_noclosingdate', 'gradebook')) . '</span>' : $escape($dateTime->formatDate($row['closing_date'])); ?></td>
                        <td class="student-gradebook__number"><?php echo $number($row['weight']); ?>%</td>
                        <td><span class="student-gradebook__status<?php echo $statusClass; ?>"><?php echo $escape($status); ?></span></td>
                        <td class="student-gradebook__number"><?php echo $row['mark_percent'] === null ? '&mdash;' : $number($row['mark_percent']) . '%'; ?></td>
                        <td class="student-gradebook__number"><?php echo $row['weighted_mark'] === null ? '&mdash;' : $number($row['weighted_mark']) . '%'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="student-gradebook__summary">
            <div class="student-gradebook__summary-card"><span class="student-gradebook__summary-label"><?php echo $escape($objLanguage->languageText('mod_gradebook_plannedcontribution', 'gradebook')); ?></span><span class="student-gradebook__summary-value"><?php echo $number($plannedContribution); ?>%</span></div>
            <div class="student-gradebook__summary-card"><span class="student-gradebook__summary-label"><?php echo $escape($objLanguage->languageText('mod_gradebook_markedcontribution', 'gradebook')); ?></span><span class="student-gradebook__summary-value"><?php echo $number($markedContribution); ?>%</span></div>
            <div class="student-gradebook__summary-card"><span class="student-gradebook__summary-label"><?php echo $escape($objLanguage->languageText('mod_gradebook_currentcoursemark', 'gradebook')); ?></span><span class="student-gradebook__summary-value"><?php echo $markedCount ? $number($currentCourseMark) . '%' : $escape($objLanguage->languageText('mod_gradebook_notavailableyet', 'gradebook')); ?></span></div>
        </div>
    <?php endif; ?>
    <p><a href="<?php echo $escape($this->uri(array('action' => null))); ?>"><?php echo $escape($objLanguage->languageText('mod_gradebook_goback', 'gradebook')); ?></a></p>
</div>
