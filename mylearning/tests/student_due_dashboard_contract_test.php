<?php
/** Static contract checks for the cross-course student due calendar. */
$root = dirname(__DIR__);
$service = file_get_contents($root . '/classes/studentdueitems_class_inc.php');
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/main_tpl.php');
$register = file_get_contents($root . '/register.conf');
$essay = file_get_contents(dirname($root) . '/essay/classes/essayassessmentprovider_class_inc.php');

$checks = array(
    'dashboard loads the due-item service' => strpos($controller, "getObject('studentdueitems', 'mylearning')") !== false,
    'calendar precedes course overview' => strpos($template, '$dueItems . $learningOverview') !== false,
    'discovers generic assessment adapters' => strpos($service, '$this->providers->all()') !== false
        && strpos($service, '$this->providers->adapter(') !== false,
    'only enabled course tools contribute' => strpos($service, 'getContextModules($contextCode)') !== false
        && strpos($service, 'isset($enabled[$provider[\'module_id\']])') !== false,
    'duplicate role memberships do not duplicate due items' => strpos($service, 'array_unique(') !== false,
    'uses canonical time service' => strpos($service, "getObject('timeanddateservice', 'timeanddate-service')") !== false,
    'normalises result and launch target' => strpos($service, 'getStudentResult(') !== false
        && strpos($service, 'getLaunchTarget(') !== false,
    'provider failures are isolated' => strpos($service, 'catch (Throwable $failure)') !== false,
    'actions have tooltip and accessible name' => strpos($service, 'aria-label=') !== false
        && strpos($service, 'title=') !== false
        && strpos($service, "render('arrow-right'") !== false,
    'shows days and result status' => strpos($service, 'dashboard-days-badge') !== false
        && strpos($service, 'dashboard-agenda-item__status') !== false,
    'shows an available mark as a percentage' => strpos($service, "render('percent'") !== false
        && strpos($service, "['mark_percent']") !== false,
    'essay provider exposes its due date' => strpos($essay, "'closing_date'=>") !== false,
    'manifest declares dependencies' => strpos($register, 'DEPENDS: gradebook') !== false
        && strpos($register, 'DEPENDS: timeanddate-service') !== false,
);

$failed = array_keys(array_filter($checks, static function ($passed) { return !$passed; }));
if ($failed !== array()) {
    fwrite(STDERR, 'Failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}
echo 'Student due dashboard contracts passed (' . count($checks) . ').' . PHP_EOL;
