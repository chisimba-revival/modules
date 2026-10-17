<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { exit; }
// Both portfolio layouts share a persistent, escaped import status notice.
$portfolioNotices = [
    'uploadsuccessful' => ['mod_eportfolio_successMessage', 'success'],
    'sorryemptypdf' => ['phrase_eportfolio_emptypdfmessage', 'error'],
];
$portfolioMessage = $this->getParam('message');
if (is_string($portfolioMessage) && isset($portfolioNotices[$portfolioMessage])) {
    [$portfolioKey, $portfolioStyle] = $portfolioNotices[$portfolioMessage];
    echo '<div class="chisimba-notice chisimba-notice--' . $portfolioStyle . '" role="status">'
        . htmlspecialchars($this->objLanguage->languageText($portfolioKey, 'eportfolio'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</div>';
}
