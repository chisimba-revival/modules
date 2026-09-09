<?php
/** Maintenance help authorisation, registered text and guide parity.
 * @author Derek Keats
 * @package systemmanagement
 */
class ChisimbaObject {}
require dirname(__DIR__) . '/classes/helpcontent_class_inc.php';
$provider = new helpcontent();
$user = new class { public $admin = true; public function isAdmin() { return $this->admin; } };
$language = new class {
    public function code2Txt($key, $module) {
        foreach (file(dirname(__DIR__) . '/register.conf') as $line) {
            if (str_starts_with($line, 'TEXT: ' . $key . '|')) { return explode('|', trim($line), 3)[2]; }
        }
        throw new RuntimeException('Missing registered help text: ' . $key);
    }
};
(new ReflectionProperty($provider, 'user'))->setValue($provider, $user);
(new ReflectionProperty($provider, 'language'))->setValue($provider, $language);
foreach (array('maintenance', 'deployment-runbook') as $id) {
    $topic = $provider->getTopic($id);
    if (!$topic || count($topic['steps']) < 6) { throw new RuntimeException('Incomplete guide: ' . $id); }
    $doc = file_get_contents(dirname(__DIR__) . '/docs/' . $id . '.md');
    foreach (array_merge(array($topic['title'], $topic['summary']), $topic['steps'], array_column($topic['sections'], 'body')) as $text) {
        if (!str_contains($doc, $text)) { throw new RuntimeException('Help and runbook differ: ' . $id); }
    }
    $user->admin = false;
    if ($provider->mayViewTopic($id) || $provider->getTopic($id) !== null) { throw new RuntimeException('Non-admin guide disclosure'); }
    $user->admin = true;
}
if ($provider->getTopic('unknown') !== null) { throw new RuntimeException('Unknown topic accepted'); }
echo "PASS: both maintenance guides are registered, complete, document-matched and administrator-only\n";
