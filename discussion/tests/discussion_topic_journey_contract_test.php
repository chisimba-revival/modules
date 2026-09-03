<?php
/**
 * Contract checks for the modern Discussion topic and reply journey.
 *
 * @author Derek Keats
 */

$module = dirname(__DIR__);
$block = file_get_contents($module . '/classes/block_flatview_class_inc.php');
$controller = file_get_contents($module . '/controller.php');
$css = file_get_contents($module . '/resources/discussion-modern.css');

$checks = array(
    'modern topic renderer is active' => str_contains($block, 'return $this->buildModernTopic();'),
    'legacy action table is bypassed' => str_contains($block, 'discussion-topic-modern__header')
        && !str_contains(substr($block, strrpos($block, 'private function buildModernTopic')), 'htmlTable->show()'),
    'actions use skin icons' => str_contains($block, "getObject('iconservice', 'ui')")
        && str_contains($block, "render('shield-check'")
        && str_contains($block, "render('plus'"),
    'topic has semantic post region' => str_contains($block, 'aria-label="Topic posts"'),
    'notifications use an accessible details panel' => str_contains($block, '<details class="chisimba-card discussion-topic-notifications">')
        && str_contains($block, '<legend>Notify me about this conversation</legend>'),
    'notification save returns to topic' => str_contains($controller, "nextAction('flatview'")
        && str_contains($controller, "'subscriptionupdated'"),
    'responsive topic treatment exists' => str_contains($css, '.discussion-topic-modern__header')
        && str_contains($css, '.discussion-topic-modern__posts .newDiscussionContainer'),
    'legacy profile image is removed from topic layout' => str_contains($css, '.discussion-topic-modern__posts .discussionProfileImg'),
    'Derek Keats remains an author' => str_contains(file_get_contents($module . '/register.conf'), 'Derek Keats'),
);

foreach ($checks as $description => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
    echo "PASS: {$description}\n";
}
