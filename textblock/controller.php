<?php
/* ----------- controller class extends controller for tbl_quotes------------*/
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}


/**
*
* Controller class for the table tbl_quotes
*
* @author Administrative User
*
*
* @version $Id: controller.php 14552 2009-08-27 11:52:17Z jsc $
* @version $Id: controller.php,v 1.3 2006/09/14 Abdurahim Ported to PHP5
* @copyright 2005 GNU GPL
*
*/
class textblock extends controller
{

    /**
    * @var string $action The action parameter from the querystring 
    */
    public $action;
    public $objDb, $objLanguage, $objLog;

    /**
    * Standard constructor method 
    */
    public function init()
    {
        //Retrieve the action parameter from the querystring
        $this->action = $this->getParam('action', Null);
        //Create an instance of the database class for this module
        $this->objDb = $this->getObject("dbtextblock");

        //Create an instance of the language object
        $this->objLanguage = $this->getObject("language", "language");
        //Get the activity logger class
        $this->objLog=$this->newObject('logactivity', 'logger');
        //Log this module call
        $this->objLog->log();
    }

    /**
    * Standard dispatch method 
    */
    public function dispatch()
    {
        $security = $this->getObject('textblocksecurity', 'textblock');
        try {
            $security->requireAdmin();
            if (!in_array($this->action, [null,'text','view','widetext','deleteajax','ajaxedit','save'], true)) throw new DomainException('editor_invalid');
            foreach (['id','mode','blocktype'] as $key) if (!is_string($this->getParam($key, ''))) throw new DomainException('editor_invalid');
            if (!in_array($this->getParam('blocktype', ''), ['', 'text', 'widetext'], true) || !in_array($this->getParam('mode', ''), ['', 'add', 'edit'], true) || strlen($this->getParam('id', '')) > 32) throw new DomainException('editor_invalid');
            if (in_array($this->action, ['save','deleteajax'], true)) {
                $input = [];
                foreach (['revision','csrf_token','id','mode','blockid','title','blocktext','css_id','css_class','show_title'] as $key) $input[$key] = $this->getParam($key, '');
                $security->check($this->action, $input, $_SERVER['REQUEST_METHOD'] ?? '');
            }
        } catch (DomainException $error) {
            http_response_code($error->getMessage() === 'editor_method' ? 405 : 403);
            header('Content-Type: text/plain; charset=UTF-8');
            die($this->objLanguage->languageText('mod_textblock_'.$error->getMessage(), 'textblock'));
        }
        $this->appendArrayVar('headerParams', '<meta name="textblock-csrf" content="'.htmlspecialchars($security->csrf()->issue('textblock_edit'), ENT_QUOTES, 'UTF-8').'">');
        switch ($this->action) {
            case null:
            case 'text':
            case 'view':
                // Set the layout template to compatible one
                if ($this->objUser->isAdmin()) {
                    $this->setLayoutTemplate('layout_tpl.php');
                    return 'narrowblockview_tpl.php';
                } else {
                    $this->setVar('str', 
                      "<br /><span class='error'>" 
                        . $this->objLanguage->languageText(
                          "phrase_nopermission",NULL,"You have no permission for"
                        ).": ".$this->action) . "</span>";
                    return 'dump_tpl.php';
                }
                break;
            case 'widetext':
                // Set the layout template to compatible one
                if ($this->objUser->isAdmin()) {
                    $this->setLayoutTemplate('layout_tpl.php');
                    return 'wideblockview_tpl.php';
                } else {
                    $this->setVar('str', 
                      "<br /><span class='error'>" 
                        . $this->objLanguage->languageText(
                          "phrase_nopermission",NULL,"You have no permission for"
                        ).": ".$this->action) . "</span>";
                    return 'dump_tpl.php';
                }
                break;
            case 'deleteajax':
                if ($this->objUser->isAdmin()) {
                    try {
                        $this->objDb->deleteRecord($this->getParam('id', ''));
                    } catch (Throwable $error) {
                        http_response_code($error->getMessage() === 'editor_stale' ? 409 : 422);
                        header('Content-Type: text/plain; charset=UTF-8');
                        die($this->objLanguage->languageText('mod_textblock_'.($error->getMessage() === 'editor_stale' ? 'editor_stale' : 'editor_savefailed'), 'textblock'));
                    }
                    header('Content-Type: text/plain; charset=UTF-8');
                    header('Cache-Control: no-store');
                    die("RECORD_DELETED");
                } else {
                    die("ERR_NOPERMISSION");
                }
                break;
            case 'ajaxedit':
                if ($this->objUser->isAdmin()) {
                    // Set the layout template to compatible one
                    $this->setLayoutTemplate('layout_tpl.php');
                    return "editajax_tpl.php";
                } else {
                    $this->setVar('str', 
                      "<br /><span class='error'>" 
                        . $this->objLanguage->languageText(
                          "phrase_nopermission",NULL,"You have no permission for"
                        ).": ".$this->action) . "</span>";
                    return 'dump_tpl.php';
                }
                break;
            case 'save':
                if ($this->objUser->isAdmin()) {
                    try {
                        $this->objDb->saveRecord($this->getParam('mode', Null), $this->objUser->userId());
                    } catch (Throwable $error) {
                        http_response_code($error->getMessage() === 'editor_stale' ? 409 : 422);
                        header('Content-Type: text/plain; charset=UTF-8');
                        die($this->objLanguage->languageText('mod_textblock_'.($error->getMessage() === 'editor_stale' ? 'editor_stale' : 'editor_savefailed'), 'textblock'));
                    }
                    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                        header('Content-Type: application/json');
                        die(json_encode(['ok'=>true]));
                    }
                    $blockType = str_starts_with($this->getParam('blockid', ''), 'widetext') ? 'widetext' : 'text';
                    return $this->nextAction($blockType);
                } else {
                    $this->setVar('str', 
                      "<br /><span class='error'>" 
                        . $this->objLanguage->languageText(
                          "phrase_nopermission",NULL,"You have no permission for"
                        ).": ".$this->action) . "</span>";
                    return 'dump_tpl.php';
                }
                break;
            default:
                $this->setVar('str', $this->objLanguage->languageText("phrase_actionunknown",NULL,"Unknown action").": ".$this->action);
                return 'dump_tpl.php';

        }
    }
    
    /**
    * Override the default requirement for login
    */
    public function requiresLogin($action = null)
    {
        return TRUE;
    }
}
?>
