<?php
/**
 * Temporary compatibility redirect for legacy Essay Management URLs.
 *
 * All authoring and marking now lives in the context-aware Essay module.
 * This shim preserves bookmarks and stored links during the retirement window.
 *
 * @package essayadmin
 * @author Derek Keats
 */
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class essayadmin extends controller
{
    /** @return void */
    public function init() {}

    /**
     * Redirect a legacy action and its scalar query parameters to Essay.
     *
     * @param string|null $action
     * @return mixed
     */
    public function dispatch($action)
    {
        $params = array();
        foreach ((array) $_GET as $name => $value) {
            if ($name === 'module' || $name === 'action' || !is_scalar($value)) { continue; }
            $params[$name] = (string) $value;
        }
        if ((string) $action !== '') { $params['action'] = (string) $action; }
        return $this->nextAction(isset($params['action']) ? $params['action'] : null, $params, 'essay');
    }
}
?>
