<?php
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

$this->setLayoutTemplate('gradebook_layout_tpl.php');

$studentUserId = $this->objUser->userId();
$contextObject = $this->getObject('dbcontext', 'context');
$contextCode = $contextObject->getContextCode();
$rows = $this->studentAssessmentRows($studentUserId);

$escape = function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$number = function ($value) {
    return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
};

$statusLabels = array(
    'not_attempted' => $objLanguage->languageText('mod_gradebook_result_notattempted', 'gradebook'),
    'in_progress' => $objLanguage->languageText('mod_gradebook_result_inprogress', 'gradebook'),
    'submitted' => $objLanguage->languageText('mod_gradebook_result_submitted', 'gradebook'),
    'marked' => $objLanguage->languageText('mod_gradebook_result_marked', 'gradebook')
);
?>
<div class="student-gradebook chisimba-workspace">
    <h1><?php
        echo $escape(
            ($contextCode ? $contextObject->getMenuText($contextCode).' ' : '')
            .$objLanguage->languageText('mod_gradebook_title', 'gradebook')
        );
    ?></h1>

    <p>
        <strong><?php echo $escape($this->objUser->fullname($studentUserId)); ?></strong><br>
        <?php echo $escape($objLanguage->languageText('mod_gradebook_studentNumber', 'gradebook')); ?>:
        <?php echo $escape($this->objUser->username($studentUserId)); ?>
    </p>

    <?php if (empty($rows)): ?>
        <p><?php echo $escape($objLanguage->languageText('mod_gradebook_noassessmentplanstudent', 'gradebook')); ?></p>
    <?php else: ?>
        <div class="chisimba-table-wrap">
            <table class="chisimba-table student-gradebook__table">
                <thead>
                    <tr>
                        <th><?php echo $escape($objLanguage->languageText('mod_gradebook_assessment', 'gradebook')); ?></th>
                        <th><?php echo $escape($objLanguage->languageText('mod_gradebook_provider', 'gradebook')); ?></th>
                        <th><?php echo $escape($objLanguage->languageText('mod_gradebook_activitystatus', 'gradebook')); ?></th>
                        <th><?php echo $escape($objLanguage->languageText('mod_gradebook_mark', 'gradebook')); ?></th>
                        <th><?php echo $escape($objLanguage->languageText('mod_gradebook_weighting', 'gradebook')); ?></th>
                        <th><?php echo $escape($objLanguage->languageText('mod_gradebook_contributionyearmark', 'gradebook')); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $status = isset($statusLabels[$row['status']])
                        ? $statusLabels[$row['status']]
                        : $statusLabels['not_attempted'];
                ?>
                    <tr>
                        <td><?php echo $escape($row['title']); ?></td>
                        <td><?php echo $escape($row['provider']); ?></td>
                        <td><?php echo $escape($status); ?></td>
                        <td><?php echo $row['mark_percent'] === null
                            ? '&mdash;'
                            : $escape($number($row['mark_percent'])).'%'; ?></td>
                        <td><?php echo $escape($number($row['weight'])); ?>%</td>
                        <td><?php echo $row['weighted_mark'] === null
                            ? '&mdash;'
                            : $escape($number($row['weighted_mark'])).'%'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
