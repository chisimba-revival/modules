<?php
/** No database or mail transport: closed requests must stop before either. */
$GLOBALS['kewl_entry_point_run'] = true;
class dbTable {}
class controller {
    public function setLayoutTemplate($value) {}
    public function setVar($key, $value) {}
}
require dirname(__DIR__).'/classes/registrationservice_class_inc.php';
require dirname(__DIR__).'/controller.php';
class SettingRegistrationService extends registrationservice {
    public $objConfig;
}
$config = new class {
    public $value = 'FALSE';
    public function getallowSelfRegister() { return $this->value; }
};
$service = new SettingRegistrationService();
$service->objConfig = $config;
$controller = new registration_service();
$property = new ReflectionProperty(registration_service::class, 'service');
$property->setValue($controller, $service);
foreach (array('FALSE', '', 'garbage') as $value) {
    $config->value = $value;
    $result = $service->createPending(array());
    if (!empty($result['ok']) || $result['code'] !== 'registration_disabled') {
        throw new RuntimeException('Closed service accepted request');
    }
    foreach (array('', 'default', 'register', 'usernameavailability') as $action) {
        if ($controller->dispatch($action) !== 'registration_disabled_tpl.php' || http_response_code() !== 403) {
            throw new RuntimeException('Closed public route was not denied');
        }
    }
}
$config->value = 'TRUE';
if (!$service->publicRegistrationEnabled()) { throw new RuntimeException('Reopening failed'); }
echo "PASS: service and four public routes fail closed; reopening supported.\n";
