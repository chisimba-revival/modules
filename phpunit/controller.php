<?php
/**
 * Disable the obsolete browser-based test-code generator pending its retirement.
 * This is unrelated to the PHPUnit CLI testing library.
 *
 * @package phpunit
 * @author Derek Keats
 * @license GNU GPL
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}
class phpunit extends controller
{
    /** Never initialise the old generator, analyser, storage or UI. */
    public function init() {}

    /** All identities receive the same inert notice, including old action URLs. */
    public function requiresLogin($action = null) { return false; }

    public function dispatch($action = null)
    {
        http_response_code(410);
        $this->setPageTemplate(null);
        $this->setLayoutTemplate(null);
        $this->setVar('pageSuppressToolbar', true);
        return 'unavailable_tpl.php';
    }
}
