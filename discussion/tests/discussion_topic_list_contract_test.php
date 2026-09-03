<?php
/**
 * Contract checks for the modern Discussion topic list.
 *
 * @author Derek Keats
 */

$module = dirname(__DIR__);
$block = file_get_contents($module . '/classes/block_discussionview_class_inc.php');
$css = file_get_contents($module . '/resources/discussion-modern.css');

$checks = array(
    'modern view is active' => str_contains($block, 'return $this->buildModernDiscussionView();'),
    'topics use semantic cards' => str_contains($block, '<article class="chisimba-card discussion-topic-card">'),
    'icons come from the skin service' => str_contains($block, "getObject('iconservice', 'ui')"),
    'metrics use definition-list semantics' => str_contains($block, '<dl class="discussion-topic-card__metrics">'),
    'relative dates cannot leak formatter markup' => str_contains($block, 'strip_tags($translatedDate->getDifference'),
    'small-screen treatment exists' => str_contains($css, '@media (max-width: 42rem)'),
    'Derek Keats remains an author' => str_contains(file_get_contents($module . '/register.conf'), 'Derek Keats'),
);

foreach ($checks as $description => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
    echo "PASS: {$description}\n";
}
