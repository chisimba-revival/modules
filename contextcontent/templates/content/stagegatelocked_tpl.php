<?php
$this->loadClass('link', 'htmlelements');
$gateTitle = htmlspecialchars($stageGateLocked['testname'], ENT_QUOTES, 'UTF-8');
$passMark = (int) $stageGateLocked['passmark'];
$best = $stageGateLockedBestPercentage === NULL ? '' : ' ' . htmlspecialchars(
    $this->objLanguage->languageText('mod_contextcontent_stage_gate_best_score', 'contextcontent')
    . ': ' . number_format($stageGateLockedBestPercentage, 1) . '%', ENT_QUOTES, 'UTF-8');
$quiz = new link($this->uri(array('action' => 'answertest', 'id' => $stageGateLocked['testid']), 'mcqtests'));
$quiz->link = $this->objLanguage->languageText('mod_contextcontent_stage_gate_open_quiz', 'contextcontent');
$course = new link($this->uri(array('action' => 'showcontextchapters')));
$course->link = $this->objLanguage->languageText('mod_contextcontent_stage_gate_return_course', 'contextcontent');
echo '<section class="contextcontent-stage-gate contextcontent-stage-gate-pending" aria-labelledby="contextcontent-stage-gate-locked-title">'
    . '<h1 id="contextcontent-stage-gate-locked-title">' . htmlspecialchars($this->objLanguage->languageText('mod_contextcontent_stage_gate_locked_heading', 'contextcontent'), ENT_QUOTES, 'UTF-8') . '</h1>'
    . '<p>' . htmlspecialchars($this->objLanguage->languageText('mod_contextcontent_stage_gate_locked_message', 'contextcontent'), ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>' . $gateTitle . '</strong></p>'
    . '<p>' . htmlspecialchars($this->objLanguage->languageText('mod_contextcontent_stage_gate_requirement', 'contextcontent') . ': ' . $passMark . '%', ENT_QUOTES, 'UTF-8') . $best . '</p>'
    . '<p>' . $quiz->show() . ' &nbsp; ' . $course->show() . '</p></section>';
$this->appendArrayVar('headerParams', '<style type="text/css">.contextcontent-stage-gate{max-width:760px;margin:2rem auto;padding:1.4rem;border:1px solid #e0b45b;border-left:6px solid #b7791f;border-radius:10px;background:#fffaf0}.contextcontent-stage-gate h1{margin-top:0}.contextcontent-stage-gate a{font-weight:700}</style>');
?>