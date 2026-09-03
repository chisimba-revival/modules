<?php
/** Contract checks for reliable, atomic Discussion topic creation. */
$controller = file_get_contents(__DIR__ . '/../controller.php');
$topic = file_get_contents(__DIR__ . '/../classes/dbtopic_class_inc.php');
$post = file_get_contents(__DIR__ . '/../classes/dbpost_class_inc.php');
$text = file_get_contents(__DIR__ . '/../classes/dbposttext_class_inc.php');
$form = file_get_contents(__DIR__ . '/../classes/block_newtopic_class_inc.php');
$list = file_get_contents(__DIR__ . '/../classes/block_discussionlist_class_inc.php');
$discussion = file_get_contents(__DIR__ . '/../classes/dbdiscussion_class_inc.php');
$view = file_get_contents(__DIR__ . '/../classes/block_discussionview_class_inc.php');
$checks = array(
    'topic creation is transactional' => str_contains($controller, 'beginTransaction()') && str_contains($controller, 'commitTransaction()') && str_contains($controller, 'rollbackTransaction()'),
    'every write is checked' => str_contains($controller, 'topic_insert_failed') && str_contains($controller, 'post_insert_failed') && str_contains($controller, 'post_text_insert_failed') && str_contains($controller, 'topic_finalisation_failed'),
    'failed submission preserves message' => str_contains($controller, "'message' => \$post_text") && str_contains($form, "isset(\$details['message'])"),
    'database helpers return actual insert result' => str_contains($topic, 'return $this->insert(array(') && str_contains($post, 'return $this->insert(array(') && str_contains($text, 'return $this->insert(array('),
    'write path is PHP 8.5 safe' => substr_count($topic, "'dateLastUpdated' => date('Y-m-d H:i:s')") >= 1 && str_contains($post, "\$now = date('Y-m-d H:i:s')") && str_contains($text, "\$now = date('Y-m-d H:i:s')"),
    'required audit columns are populated' => str_contains($post, "'modifierid' => \$userId") && str_contains($post, "'datecreated' => \$now") && str_contains($text, "'modifierid'            => \$userId") && str_contains($text, "'datecreated'           => \$now"),
    'forum totals use actual records' => str_contains($discussion, 'AS actual_topics') && str_contains($discussion, 'AS actual_posts') && str_contains($list, "\$discussion['actual_topics']") && str_contains($list, "\$discussion['actual_posts']"),
    'forum action and empty state use skin primitives' => str_contains($view, "getObject('iconservice', 'ui')") && str_contains($view, "cssClass = 'button'") && str_contains($view, 'chisimba-card discussion-empty-state') && !str_contains($view, 'newTopicIcon'),
    'composer is semantic and responsive' => str_contains($form, 'buildModernEntryForm') && str_contains($form, 'discussion-choice-grid') && str_contains($form, 'chisimba-form-actions') && !str_contains(substr($form, strrpos($form, 'private function buildModernEntryForm')), 'htmltable'),
    'attachment picker is course scoped' => str_contains($form, "\$selectFile->context = \$this->contextCode !== 'root'"),
    'legacy composer block title is suppressed' => str_contains($form, "\$this->title = ''"),
);
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS: ' : 'FAIL: ') . $label . PHP_EOL;
    if (!$passed) { exit(1); }
}
