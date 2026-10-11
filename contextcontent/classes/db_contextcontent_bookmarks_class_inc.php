<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class db_contextcontent_bookmarks extends dbtable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_contextcontent_bookmarks');
    }

    public function setBookmark($contextCode, $placementId, $userId, $enabled)
    {
        $where = array('contextcode' => $contextCode, 'placementid' => $placementId, 'userid' => $userId);
        $existing = $this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE contextcode = ' . '?'
            . ' AND placementid = ' . '?'
            . ' AND userid = ' . '?'),[$contextCode,$placementId,$userId]);
        if (!$enabled) {
            foreach ($existing as $row) { $this->delete('id', $row['id']); }
            return true;
        }
        if (count($existing) === 0) {
            $where['datecreated'] = date('Y-m-d H:i:s');
            return $this->insert($where);
        }
        return $existing[0]['id'];
    }

    public function idsForUser($contextCode, $userId)
    {
        $rows = $this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE contextcode = ' . '?'
            . ' AND userid = ' . '?'
            . ' ORDER BY datecreated DESC'),[$contextCode,$userId]);
        $ids = array();
        foreach ($rows as $row) { $ids[] = $row['placementid']; }
        return $ids;
    }


}
?>
