<?php
$gateTitle = htmlspecialchars($stageGateLocked['testname'], ENT_QUOTES, 'UTF-8');
$passMark = (int) $stageGateLocked['passmark'];
$best = $stageGateLockedBestPercentage === NULL ? '' : ' ' . htmlspecialchars(
    $this->objLanguage->languageText('mod_contextcontent_stage_gate_best_score', 'contextcontent')
    . ': ' . number_format($stageGateLockedBestPercentage, 1) . '%', ENT_QUOTES, 'UTF-8');
$quizUrl = $this->uri(array('action' => 'startstagegatequiz', 'id' => $stageGateLocked['chapterid']));
$chapterUrl = $this->uri(array('action' => 'viewchapter', 'id' => $stageGateLocked['chapterid']));
$quizLabel = htmlspecialchars(
    !empty($stageGateLocked['inprogress'])
        ? $this->objLanguage->languageText(
            'mod_contextcontent_stage_gate_continue_quiz',
            'contextcontent',
            'Continue assessment'
        )
        : $this->objLanguage->languageText(
            'mod_contextcontent_stage_gate_open_quiz',
            'contextcontent'
        ),
    ENT_QUOTES,
    'UTF-8'
);
$chapterLabel = htmlspecialchars($this->objLanguage->languageText('mod_contextcontent_stage_gate_return_course', 'contextcontent'), ENT_QUOTES, 'UTF-8');
echo '<section class="contextcontent-stage-gate contextcontent-stage-gate-pending" aria-labelledby="contextcontent-stage-gate-locked-title">'
    . '<h1 id="contextcontent-stage-gate-locked-title">' . htmlspecialchars($this->objLanguage->languageText('mod_contextcontent_stage_gate_locked_heading', 'contextcontent'), ENT_QUOTES, 'UTF-8') . '</h1>'
    . '<p>' . htmlspecialchars($this->objLanguage->languageText('mod_contextcontent_stage_gate_locked_message', 'contextcontent'), ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>' . $gateTitle . '</strong></p>'
    . '<p>' . htmlspecialchars($this->objLanguage->languageText('mod_contextcontent_stage_gate_requirement', 'contextcontent') . ': ' . $passMark . '%', ENT_QUOTES, 'UTF-8') . $best . '</p>'
    . (empty($stageGateLocked['entryavailable'])
        ? '<p class="contextcontent-stage-gate-unavailable">'
            . htmlspecialchars(
                $this->objLanguage->languageText(
                    'mod_contextcontent_stage_gate_unavailable',
                    'contextcontent',
                    'This assessment is not currently open for entry.'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) . '</p><p><a class="contextcontent-stage-gate-return" href="' . $chapterUrl . '">' . $chapterLabel . '</a></p>'
        : '<p><a class="contextcontent-stage-gate-action" href="' . $quizUrl . '">' . $quizLabel . '</a> &nbsp; '
            . '<a class="contextcontent-stage-gate-return" href="' . $chapterUrl . '">' . $chapterLabel . '</a></p>')
    . '</section>';
$this->appendArrayVar('headerParams', '<style type="text/css">.contextcontent-stage-gate{max-width:760px;margin:2rem auto;padding:1.4rem;border:1px solid #e0b45b;border-left:6px solid #b7791f;border-radius:10px;background:#fffaf0}.contextcontent-stage-gate h1{margin-top:0}.contextcontent-stage-gate a{font-weight:700}</style>');
?>