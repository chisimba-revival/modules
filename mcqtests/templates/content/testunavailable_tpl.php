<?php
$this->loadClass('link', 'htmlelements');

$title = $this->objLanguage->languageText(
    'mod_mcqtests_testunavailable_heading',
    'mcqtests',
    'Assessment not currently available'
);
$message = $this->objLanguage->languageText(
    'mod_mcqtests_testunavailable_message',
    'mcqtests',
    'This assessment is not currently open for entry. No attempt has been started.'
);

if (!empty($unavailableStageGateOriginChapter)) {
    $returnUrl = $this->uri(
        array('action' => 'viewchapter', 'id' => $unavailableStageGateOriginChapter),
        'contextcontent'
    );
    $returnLabel = $this->objLanguage->languageText(
        'mod_mcqtests_testunavailable_returncourse',
        'mcqtests',
        'Return to chapter'
    );
} else {
    $returnUrl = $this->uri(array('action' => 'newhome'), 'mcqtests');
    $returnLabel = $this->objLanguage->languageText(
        'mod_mcqtests_testunavailable_returntests',
        'mcqtests',
        'Return to assessments'
    );
}

echo '<section class="mcq-test-unavailable" aria-labelledby="mcq-test-unavailable-title">'
    . '<h1 id="mcq-test-unavailable-title">'
    . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
    . '</h1>'
    . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><a class="mcq-test-unavailable-action" href="'
    . htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($returnLabel, ENT_QUOTES, 'UTF-8') . '</a></p>'
    . '</section>';

$this->appendArrayVar(
    'headerParams',
    '<style type="text/css">'
    . '.mcq-test-unavailable{max-width:760px;margin:2rem auto;padding:1.5rem 1.6rem;'
    . 'border:1px solid #d6dce0;border-left:6px solid #607d8b;border-radius:10px;background:#fff}'
    . '.mcq-test-unavailable h1{margin-top:0}'
    . '.mcq-test-unavailable-action{display:inline-block;padding:.7rem 1rem;border-radius:6px;'
    . 'background:#295351;color:#fff!important;text-decoration:none;font-weight:700}'
    . '</style>'
);
?>
