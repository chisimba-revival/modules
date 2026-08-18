<?php
$language = $this->objLanguage;
$L = function ($k) use ($language) {
    return $language->languageText('mod_offlineassessment_'.$k, 'offlineassessment');
};
echo '<h1>'.htmlspecialchars($assessment['name']).' — '.htmlspecialchars($L('audit')).'</h1>';
if (empty($history)) {
    echo '<p>'.$L('nomarkhistory').'</p>';
} else {
    echo '<table class="table"><thead><tr><th>'.$L('when').'</th><th>'.$L('student').'</th><th>'.$L('oldmark').'</th><th>'.$L('newmark').'</th><th>'.$L('changedby').'</th><th>'.$L('reason').'</th></tr></thead><tbody>';
    foreach ($history as $h) {
        echo '<tr><td>'.htmlspecialchars($h['date_changed']).'</td><td>'.htmlspecialchars($h['student_id']).'</td><td>'.htmlspecialchars($h['old_mark']===null?'':$h['old_mark']).'</td><td>'.htmlspecialchars($h['new_mark']).'</td><td>'.htmlspecialchars($h['changed_by']).'</td><td>'.htmlspecialchars($h['reason']).'</td></tr>';
    }
    echo '</tbody></table>';
}
echo '<p><a href="'.$this->uri(array()).'">'.$L('back').'</a></p>';
?>