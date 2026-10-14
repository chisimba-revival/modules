<?php

/**
* Context Blocks
* 
* Class to add, rearrange, move around and remove blocks from a context home page
* 
* PHP version 5
* 
* This program is free software; you can redistribute it and/or modify 
* it under the terms of the GNU General Public License as published by 
* the Free Software Foundation; either version 2 of the License, or 
* (at your option) any later version.
* This program is distributed in the hope that it will be useful, 
* but WITHOUT ANY WARRANTY; without even the implied warranty of 
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
* GNU General Public License for more details.
* You should have received a copy of the GNU General Public License 
* along with this program; if not, write to the 
* Free Software Foundation, Inc., 
* 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
* 
* @category  Chisimba
* @package   context
* @author    Tohir Solomons <tsolomons@uwc.ac.za>
* @copyright 2008 Tohir Solomons
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
* @version   $Id: dbcontextblocks_class_inc.php 3611 2008-02-26 13:31:01Z tohir $
* @link      http://avoir.uwc.ac.za
* @see       core
*/
/* -------------------- dbTable class ----------------*/
// security check - must be included in all scripts
if (!
/**
* Description for $GLOBALS
* @global entry point $GLOBALS['kewl_entry_point_run']
* @name   $kewl_entry_point_run
*/
$GLOBALS['kewl_entry_point_run']) {
die("You cannot view this page directly");
}
// end security check


/**
* Context Blocks
* 
* Class to add, rearrange, move around and remove blocks from a context home page
* 
* @category  Chisimba
* @package   context
* @author    Tohir Solomons <tsolomons@uwc.ac.za>
* @copyright 2008 Tohir Solomons
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
* @version   Release: @package_version@
* @link      http://avoir.uwc.ac.za
* @see       core
*/
class dbpersonalspaceblocks extends dbTable
{
   /**
   * @var object $objUser : The user Object
   */
   public $objUser;


    /**
    * Constructor
    */
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_personalspace_blocks');
        $this->objUser = $this->getObject('user','security');
    }
    
    /**
     * Method to get a list of blocks used in a context, and have them rendered one time
     * @param string $contextCode Context Code
     * @param string $side Side on which the blocks are on
     * @param return array
     */
    public function getUserBlocks($userId, $side)
    {
        $results = $this->getUserBlocksList($userId, $side);
        
        if ((is_countable($results) ? count($results) : 0) == 0) {
           return '';
        } else {
           
            $str = '';
            
            $objBlocks = $this->getObject('blocks', 'blocks');
            $objDynamicBlocks = $this->getObject('dynamicblocks', 'blocks');
            
            foreach ($results as $result)
            {
                // Retain an editable placement without executing a revoked or foreign block.
                if (!$this->getObject('personalspaceeditor')->allowed($result['block'], $side, $userId)) {
                    $message=$this->getObject('language','language')->languageText('mod_personalspace_unavailableblock','personalspace');
                    $str .= '<div id="'.htmlspecialchars($result['id'], ENT_QUOTES, 'UTF-8').'" class="block"><p>'.htmlspecialchars(html_entity_decode($message, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8').'</p></div>';
                    continue;
                }
                $block = explode('|', $result['block']);
                
                $blockId = $side.'___'.str_replace('|', '___', $result['block']);
                
                // At the moment, only blocks are catered for, not yet dynamic blocks
                if ($block[0] == 'block') {
                    
                    
                    $blockStr = $objBlocks->showBlock($block[1], $block[2], NULL, 20, TRUE, FALSE);
                    
                    //$blockStr = preg_replace('/onchange\ *=\ *".*?"/i', '', $blockStr);
                    //$blockStr = preg_replace('/type\ *=\ *"submit"/i', 'type="button"', $blockStr);
                    //$blockStr = preg_replace('/href\ *=\ *".*?"/i', 'href="javascript:alert(\'Disabled\');"', $blockStr);
                    
                    $str .= '<div id="'.htmlspecialchars($result['id'], ENT_QUOTES, 'UTF-8').'" class="block">'.$blockStr.'</div>';
                } else if ($block[0] == 'dynamicblock') {
                    $block = explode('|', $result['block']);
                    $blockStr = $objDynamicBlocks->showBlock($block[1]);
                    $str .= '<div id="'.htmlspecialchars($result['id'], ENT_QUOTES, 'UTF-8').'" class="block">'.$blockStr.'</div>';
                }
           }
           
           return $str;
        }
    }
    
    /**
     * Method to get a list of blocks used in a context
     * @param string $contextCode Context Code
     * @param string $side Side on which the blocks are on
     * @param return array
     */
    public function getUserBlocksList($userId, $side)
    {
        return $this->selectPrepared('SELECT * FROM tbl_personalspace_blocks WHERE side=? AND userid=? ORDER BY position, id', [$side, $userId]);
    }
    
    
    /**
     * Method to get a list of blocks used by a context
     * @param string $contextCode
     * @return array List of Blocks
     */
    public function getUserBlocksArray($userId)
    {
        $results = $this->editingRows($userId);
        
        $array = array();
        
        if ((is_countable($results) ? count($results) : 0) > 0) {
            foreach ($results as $result)
            {
                $array[] = $result['block'];
            }
        }
        
        return $array;
    }
    
    
    /** Stable, owner-scoped snapshot shared by page rendering and saves. */
    public function editingRows($userId)
    {
        return $this->selectPrepared('SELECT * FROM tbl_personalspace_blocks WHERE userid=? ORDER BY side, position, id', [$userId]);
    }

    public function editingRevision($userId)
    {
        return hash('sha256', json_encode($this->editingRows($userId)));
    }

    public function editPlacement($userId, $revision, $action, $id, $side, $direction)
    {
        if (!$this->objUser->isLoggedIn() || $userId !== $this->objUser->userId()) throw new RuntimeException('block_denied');
        return $this->getObject('blockplacementorder', 'blocks')->edit(
            $this, $userId, $revision, $action, $id, $side, $direction, ['userid'=>$userId]
        );
    }
}
