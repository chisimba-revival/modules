<?php
/* ----------- data class extends dbTable for tbl_quotes------------*/
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run'])
    {
        die("You cannot view this page directly");
    }


/**
* Model class for the table tbl_textblock
*
* @author Derek Keats
*
* $Id: dbtextblock_class_inc.php,v 1.1 2006/09/14 08:19:14 dkeats
*
*/
class dbtextblock extends dbTable
{
    private $objBlock;

    /**
    * Constructor method to define the table
    */
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {
        parent::init('tbl_textblock');

		$this->objBlock = $this->getObject('dbblocksdata', 'blocks');
    }

    /**
    * Method to return the block row
    * @param string $blockId the blockId as per tbl_module_blocks.id.
    */
    public function getBlock($blockId)
    {
        $blockArr = $this->objBlock->getRow('id', $blockId);
        if (!$blockArr || $blockArr['moduleid'] !== 'textblock') {
            return null;
        }
        $result = $this->selectPrepared(
            'SELECT * FROM tbl_textblock WHERE blockid = ?',
            [trim($blockArr['blockname'])]
        );
        return $result[0] ?? null;
    }
    
    /**
    * Method to return the block row by id
    * @param string $id the id as per tbl_module_blocks.blockid.
    */
    public function getBlockById($id)
    {   
        return $this->selectPrepared('SELECT * FROM tbl_textblock WHERE id = ?', [$id]);
    }
    
    /** 
     *
     * Get an array of what blocks of a particular type
     * are used.
     * 
     * @param string $blockType  Type of block (text, widetext)
     * @return string array of used blocks
     * @access public
     * 
     */
    public function getArUsedBlockss($blockType)
    {
        if (!in_array($blockType, ['text', 'widetext'], true)) {
            return null;
        }
        $ar = $this->selectPrepared(
            'SELECT blockid FROM tbl_textblock WHERE blockid LIKE ?',
            [$blockType . '%']
        );
        if (!empty($ar)) {
            foreach($ar as $entry) {
                $newAr[] = $entry['blockid'];
            }
            return $newAr;
        } else {
            return NULL;
        }
    }

    /**
    * Save method for editing a record in this table
    * @param string $mode: edit if coming from edit, add if coming from add
    */
    public function saveRecord($mode, $userId)
    {
        $id = $this->getParam('id', '');
        $blockid = $this->getParam('blockid', '');
        $values = [
            'blockid' => $blockid,
            'title' => trim($this->getParam('title', '')),
            'blocktext' => $this->getObject('richtextsanitizer','utilities')->cleanHtml($this->getParam('blocktext', '')),
            'css_id' => $this->getParam('css_id', ''),
            'css_class' => $this->getParam('css_class', ''),
            'show_title' => $this->getParam('show_title', '') === 'on' ? '1' : '0',
            'modified' => $this->now()
        ];
        return $this->withDatabaseLock('textblock-edit', function () use ($id, $blockid, $mode, $userId, $values) {
            return $this->transaction(function () use ($id, $blockid, $mode, $userId, $values) {
                $duplicates = $this->selectPrepared('SELECT id FROM tbl_textblock WHERE blockid = ? AND id <> ?', [$blockid, $id === '' ? '-' : $id]);
                if ($duplicates) throw new DomainException('Block slot already used');
                if ($mode === 'edit') {
                    if (!$this->getBlockById($id)) throw new DomainException('Missing block');
                    if (!$this->update('id', $id, $values + ['datemodified'=>$this->now(), 'modifierid'=>$userId])) throw new RuntimeException('Save failed');
                    return $id;
                }
                if ($mode !== 'add') throw new DomainException('Invalid mode');
                $saved = $this->insert($values + ['datecreated'=>$this->now(), 'creatorid'=>$userId]);
                if (!$saved) throw new RuntimeException('Save failed');
                return $saved;
            });
        });
    }
}
