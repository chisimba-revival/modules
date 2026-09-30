<?php
/** Existing seven-day configuration must not undo the agreed 24-hour limit. */
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {
    public function getObject($name, $module = null) { return $GLOBALS['config']; }
}
require dirname(__DIR__).'/classes/registrationguard_class_inc.php';
$GLOBALS['config'] = new class {
    public $value;
    public function getValue($key, $module, $default) { return $this->value ?? $default; }
};
$guard = new registrationguard();
foreach ([null, 1, '1', 7, '7', 90, 0, -1, 'invalid'] as $legacyValue) {
    $GLOBALS['config']->value = $legacyValue;
    if ($guard->pendingDays() !== 1) throw new RuntimeException('Confirmation lifetime exceeded 24 hours');
}
echo "PASS: registration confirmation lifetime, including legacy seven-day configuration\n";
