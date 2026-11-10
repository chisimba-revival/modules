<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/**
* Controller for Personal Space module - This module missing most of extra functionality (just basic page)
* @author PHP4 module by Jeremy O'Connor (ported to PHP 5 by Alastair Pursch)
* @copyright 2006 University of the Western Cape
* $Id$
*/
class personalspace extends controller
{
    /*User object for security
    @var object $objUser */
    public $objUser;
    //var $dbPersonalInfoImport;

    /**
    * The Init function
    */
    public function init()
    {
        
        // Create an instance of the module object
        $this->objModule = $this->getObject('modules', 'modulecatalogue');
        $this->objBlocks = $this->getObject('blocks', 'blocks');
        
        //Check if contentblocks is installed
        $this->cbExists = $this->objModule->checkIfRegistered("contentblocks");
        if ($this->cbExists) {
            $this->objBlocksContent = $this->getObject('dbcontentblocks', 'contentblocks');
        }
        
        $this->objContextBlocks = $this->getObject('dbcontextblocks', 'context');
        $this->objDynamicBlocks = $this->getObject('dynamicblocks', 'blocks');
        $this->objUser = $this->getObject('user', 'security');
        
        $this->userId = $this->objUser->userId();
        
        $this->objPersonalSpaceBlocks = $this->getObject('dbpersonalspaceblocks');

        //Get the activity logger class
        $this->objLog=$this->newObject('logactivity', 'logger');
        $this->objLog->log();
    }

    /**
    * Standard Dispatch Function for Controller
    *
    * @access public
    * @param string $action Action being run
    * @return string Filename of template to be displayed
    */
    public function dispatch($action)
    {
        // Method to set the layout template for the given action
        $this->setLayoutTemplate('contextlayout_tpl.php');

        /*
        * Convert the action into a method (alternative to
        * using case selections)
        */
        $method = $this->getMethod($action);
        /*
        * Return the template determined by the method resulting
        * from action
        */
        return $this->$method();
    }
    
    /**
    *
    * Method to convert the action parameter into the name of
    * a method of this class.
    *
    * @access private
    * @param string $action The action parameter passed byref
    * @return string the name of the method
    *
    */
    protected function getMethod(& $action)
    {
        if ($this->validAction($action)) {
            return '__'.$action;
        } else {
            return '__home';
        }
    }

    /**
    *
    * Method to check if a given action is a valid method
    * of this class preceded by double underscore (__). If it __action
    * is not a valid method it returns FALSE, if it is a valid method
    * of this class it returns TRUE.
    *
    * @access private
    * @param string $action The action parameter passed byref
    * @return boolean TRUE|FALSE
    *
    */
    protected function validAction(& $action)
    {
        if (method_exists($this, '__'.$action)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    function __home()
    {
        // Capture before rendering blocks, so concurrent writes invalidate this page.
        $this->setVar('blockEditingRevision', $this->objPersonalSpaceBlocks->editingRevision($this->userId));
        $leftBlocks = $this->objPersonalSpaceBlocks->getUserBlocks($this->userId, 'left');
        $this->setVarByRef('leftBlocksStr', $leftBlocks);
        
        $rightBlocks = $this->objPersonalSpaceBlocks->getUserBlocks($this->userId, 'right');
        $this->setVarByRef('rightBlocksStr', $rightBlocks);
        
        $middleBlocks = $this->objPersonalSpaceBlocks->getUserBlocks($this->userId, 'middle');
        $this->setVarByRef('middleBlocksStr', $middleBlocks);
        
        $smallDynamicBlocks = $this->objDynamicBlocks->getSmallUserBlocks($this->userId);
        $this->setVarByRef('smallDynamicBlocks', $smallDynamicBlocks);
        
        $wideDynamicBlocks = $this->objDynamicBlocks->getWideUserBlocks($this->userId);
        $this->setVarByRef('wideDynamicBlocks', $wideDynamicBlocks);
        
        $objBlocks = $this->getObject('dbmoduleblocks', 'modulecatalogue');
        $smallBlocks = $objBlocks->getBlocks('normal', 'site|user');
        $this->setVarByRef('smallBlocks', $smallBlocks);
        
        $wideBlocks = $objBlocks->getBlocks('wide', 'site|user');
        $this->setVarByRef('wideBlocks', $wideBlocks);
        
        //Add content blocks if any
        $contentSmallBlocks = "";
        $contentWideBlocks = "";
        if ($this->cbExists) {
            $contentSmallBlocks = $this->objBlocksContent->getBlocksArr('content_text');
            $this->setVarByRef('contentSmallBlocks', $contentSmallBlocks);

            $contentWideBlocks = $this->objBlocksContent->getBlocksArr('content_widetext');
            $this->setVarByRef('contentWideBlocks', $contentWideBlocks);
        }
        
        return 'main_tpl.php';
    }
    
    /** All layout actions share the same authenticated, owner-scoped JSON boundary. */
    protected function __renderblock() { $this->blockEditorResponse('renderblock'); }
    protected function __addblock() { $this->blockEditorResponse('addblock'); }
    protected function __removeblock() { $this->blockEditorResponse('removeblock'); }
    protected function __moveblock() { $this->blockEditorResponse('moveblock'); }

    private function blockEditorResponse($action)
    {
        $input=[];
        foreach (['blockid','side','direction','revision','csrf_token'] as $key) $input[$key]=$this->getParam($key);
        $result=$this->getObject('personalspaceeditor')->handle($action, $input, $_SERVER['REQUEST_METHOD'] ?? '');
        http_response_code($result['status']);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($result);
        exit;
    }
}
