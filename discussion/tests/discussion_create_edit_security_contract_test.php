<?php
/**
 * Static contract for the modern discussion create/edit journey.
 *
 * Run with: php discussion/tests/discussion_create_edit_security_contract_test.php
 *
 * @author Derek Keats
 */

$root = dirname(__DIR__);
$block = file_get_contents($root . '/classes/block_createedit_class_inc.php');
$controller = file_get_contents($root . '/controller.php');
$css = file_get_contents($root . '/resources/discussion-modern.css');

$contracts = array(
    'modern form is the active renderer' => strpos($block, 'return $this->buildModernForm();') !== false,
    'description is a bounded textarea' => strpos($block, '<textarea id="discussion-description"') !== false
        && strpos($block, 'maxlength="2000" required') !== false,
    'form uses a one-time CSRF token' => strpos($block, "->issue('discussion_manage')") !== false
        && strpos($block, 'name="csrf_token"') !== false,
    'mutations require POST and consume the token' => strpos($controller, "REQUEST_METHOD") !== false
        && strpos($controller, '->consume(') !== false,
    'stored text is bounded' => strpos($controller, "mb_substr(trim((string) \$this->getParam('name')), 0, 50)") !== false
        && strpos($controller, "mb_substr(trim((string) \$this->getParam('description')), 0, 2000)") !== false,
    'boolean settings use an allow-list' => strpos($controller, "in_array(\$value, array('Y', 'N'), true)") !== false,
    'archive date is parsed strictly' => strpos($controller, "createFromFormat('!Y-m-d'") !== false,
    'skin icon buttons are used' => strpos($block, "\$editing ? 'save' : 'plus'") !== false
        && strpos($block, 'chisimba-button-compact') !== false,
    'form layout is responsive' => strpos($css, '.discussion-settings-grid') !== false
        && strpos($css, 'grid-template-columns: 1fr;') !== false,
    'Lobby scope is explicit' => strpos($block, "\$this->contextCode = 'root';") !== false
        && strpos($block, "\$this->contextTitle = 'Lobby';") !== false,
    'Derek Keats remains in authorship metadata' => strpos($block, '@author Derek Keats') !== false,
);

$failed = array_keys(array_filter($contracts, static function ($passed) {
    return !$passed;
}));
if ($failed) {
    fwrite(STDERR, "Discussion create/edit contract failures:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Discussion create/edit security and UI contracts passed.\n";
