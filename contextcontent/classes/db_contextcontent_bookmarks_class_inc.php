<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class db_contextcontent_bookmarks extends dbtable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_contextcontent_bookmarks');
        $this->_db = $this->objEngine->getDbObj();
    }

    public function setBookmark($contextCode, $placementId, $userId, $enabled)
    {
        $where = array('contextcode' => $contextCode, 'placementid' => $placementId, 'userid' => $userId);
        $existing = $this->getAll('WHERE contextcode = ' . $this->quote($contextCode)
            . ' AND placementid = ' . $this->quote($placementId)
            . ' AND userid = ' . $this->quote($userId));
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
        $rows = $this->getAll('WHERE contextcode = ' . $this->quote($contextCode)
            . ' AND userid = ' . $this->quote($userId)
            . ' ORDER BY datecreated DESC');
        $ids = array();
        foreach ($rows as $row) { $ids[] = $row['placementid']; }
        return $ids;
    }

    private function quote($value)
    {
        return $this->_db->quote((string) $value);
    }
}
?>
