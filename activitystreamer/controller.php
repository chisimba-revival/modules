<?php
/** Native private activity browser. @author Derek Keats
 * Replaces the legacy ExtJS controller by Wesley Nitsckie and Paul Mungai.
 * Licensed under the GNU General Public License, version 2 or later.
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class activitystreamer extends controller
{
    public function requiresLogin($action = null) { return true; }
    public function dispatch($action)
    {
        $action = strtolower((string) $action);
        $mode = $this->getParam('mode','site');
        if ($action === 'jsoncourseactivities') $mode = 'pages';
        if ($action === 'jsonlistactivities') $mode = 'site';
        $browser = $this->getObject('activitybrowser','activitystreamer');
        if (!is_string($mode) || !$browser->mayView($mode)) {
            http_response_code(403); $this->setVar('denied',true); return 'activities_tpl.php';
        }
        if (!in_array($action, ['', 'home','showactivities','jsonlistactivities','jsoncourseactivities'], true)) {
            http_response_code(404); $this->setVar('denied',true); return 'activities_tpl.php';
        }
        header('Cache-Control: private, no-store');
        if (str_starts_with($action,'json')) {
            header('Content-Type: application/json; charset=UTF-8');
            $utilities=$this->getObject('activityutilities','activitystreamer');
            echo $action === 'jsoncourseactivities'
                ? $utilities->jsonCourseActivies($this->getParam('start',0),$this->getParam('limit',30))
                : $utilities->jsonListActivity($this->getParam('start',0),$this->getParam('limit',30));
            return null;
        }
        $data=$browser->browse($mode,['q'=>$this->getParam('q',''),'sort'=>$this->getParam('sort','recent'),'page'=>$this->getParam('page',1)]);
        $this->setVar('denied',false); $this->setVar('data',$data);
        return 'activities_tpl.php';
    }
}
