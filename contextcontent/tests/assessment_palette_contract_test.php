<?php
/**
 * Contract for provider-backed assessment activities in course content.
 *
 * @author Derek Keats
 */
$root = dirname(__DIR__);
$read = static function ($path) use ($root) {
    $value = file_get_contents($root . '/' . $path);
    if ($value === false) { throw new RuntimeException('Unable to read ' . $path); }
    return $value;
};
$service = $read('classes/assessmentpaletteservice_class_inc.php');
$authoring = $read('classes/contentauthoringservice_class_inc.php');
$picker = $read('templates/content/contenttypepicker_tpl.php');
$view = $read('templates/content/viewpage_tpl.php');
$providerFiles = array('worksheet', 'assignment', 'essay', 'mcqtests', 'offlineassessment');
$providers = array();
foreach ($providerFiles as $module) {
    $providers[$module] = file_get_contents(
        $root . '/../' . $module . '/classes/' . $module . 'assessmentprovider_class_inc.php'
    );
}

$required = array(
    array($service, "getObject('assessmentproviderregistry', 'gradebook')"),
    array($service, 'getLaunchTarget'),
    array($authoring, "\$data['providermodule']"),
    array($authoring, "\$data['provideritemid']"),
    array($picker, 'mod_contextcontent_assessmentpalette'),
    array($picker, "'contenttype'=>'assessment_activity'"),
    array($view, "\$page['contenttype'] === 'assessment_activity'"),
    array($service, "getObject('dbcontextmodules', 'context')"),
    array($service, "['module_id']"),
    array((string) $providers['worksheet'], 'getLaunchTarget'),
    array((string) $providers['worksheet'], "'action' => \$role === 'author' ? 'worksheetinfo' : 'viewworksheet'"),
);
foreach ($providers as $module => $provider) {
    $required[] = array((string) $provider, 'getLaunchTarget');
}
foreach ($required as $contract) {
    if (strpos($contract[0], $contract[1]) === false) {
        fwrite(STDERR, "FAIL: incomplete provider-backed assessment palette\n");
        exit(1);
    }
}
echo "PASS: Assessment palette stores and launches provider-backed activities.\n";
