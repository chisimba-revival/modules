<?php
/**
 * Security contracts for Discussion course-content activities.
 *
 * @author Derek Keats
 */

$module = dirname(__DIR__);
$provider = file_get_contents($module . '/classes/modulelinks_discussion_class_inc.php');
$controller = file_get_contents($module . '/controller.php');

$checks = array(
    'provider validates course and record identifiers' => str_contains($provider, 'validIdentifier('),
    'provider uses the supplied course scope' => str_contains($provider, 'getContextDiscussions($contextCode)'),
    'provider uses the authenticated user identity' => str_contains($provider, '$this->user->userId()'),
    'provider does not expose individual replies' => !str_contains($provider, 'getThread('),
    'provider contains no placeholder descriptions' => !str_contains($provider, 'topic description') && !str_contains($provider, 'sffas'),
    'launches are checked before dispatch' => str_contains($controller, 'resourceBelongsToActiveScope($action)'),
    'cross-scope comparisons are timing safe' => str_contains($controller, 'hash_equals('),
    'reply relationship is validated' => str_contains($controller, 'replyTargetBelongsToActiveScope()'),
    'query parameters cannot switch courses' => !str_contains($controller, 'passthroughlogin') && !str_contains($controller, 'updatePassThroughLogin'),
    'legacy passthrough service is removed' => !file_exists($module . '/classes/discussion_passthrough_class_inc.php'),
    'Derek Keats remains module author' => str_contains(file_get_contents($module . '/register.conf'), 'Derek Keats'),
);

foreach ($checks as $description => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
    echo "PASS: {$description}\n";
}
