<?php
/** Safe external-media consumer; legacy publishing routes are unavailable. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class oembed extends controller
{
    public function init() {}
    public function requiresLogin($action = null) { return false; }
    public function dispatch()
    {
        $action = $this->getParam('action','demo');
        if ($action === 'demo') {
            $this->setVar('str', $this->getObject('nativeembed','oembed')->link($this->getParam('embed','https://vimeo.com/3108686')));
            return 'demo_tpl.php';
        }
        $this->setPageTemplate(null); $this->setLayoutTemplate(null);
        $this->setVar('pageSuppressToolbar',true);
        if ($action === 'resolve') {
            // Pure URL classification: no network, stored content or writes.
            $media = null;
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                http_response_code(405); header('Allow: POST');
            } else $media = $this->getObject('nativeembed','oembed')->describe($_POST['url'] ?? null);
            $this->setVar('media',$media);
            return 'resolve_tpl.php';
        }
        http_response_code(410);
        return 'unavailable_tpl.php';
    }
}
