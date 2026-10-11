<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class db_ingestservice_runs extends dbtable
{
    /** Dependencies and state initialised by this component. */
    public $objUser;

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback')
    {
        parent::init('tbl_ingestservice_runs', $pearDb, $errorCallback);
        $this->objUser = $this->getObject('user', 'security');
    }

    public function findCompleted($fingerprint, $consumer, $target)
    {
        $row = $this->find($fingerprint, $consumer, $target);
        return $row && $row['status'] === 'completed' ? $row : false;
    }

    private function find($fingerprint,$consumer,$target) {$rows=$this->selectPrepared('SELECT * FROM tbl_ingestservice_runs WHERE sourcefingerprint=? AND consumer=? AND consumertarget=? LIMIT 1',[$fingerprint,$consumer,$target]);return $rows[0]??false;}

    public function start($fingerprint, $consumer, $target)
    {
        $existing = $this->find($fingerprint, $consumer, $target);
        if ($existing) {
            $this->update('id', $existing['id'], array(
                'status' => 'started',
                'resultreference' => null,
                'creatorid' => $this->objUser->userId(),
                'datecreated' => date('Y-m-d H:i:s'),
                'datecompleted' => null
            ));
            return $existing['id'];
        }
        return $this->insert(array(
            'sourcefingerprint' => $fingerprint,
            'consumer' => $consumer,
            'consumertarget' => $target,
            'status' => 'started',
            'creatorid' => $this->objUser->userId(),
            'datecreated' => date('Y-m-d H:i:s')
        ));
    }

    public function complete($id, array $reference)
    {
        return $this->update('id', $id, array(
            'status' => 'completed',
            'resultreference' => json_encode($reference),
            'datecompleted' => date('Y-m-d H:i:s')
        ));
    }

    public function fail($id, array $issues)
    {
        return $this->update('id', $id, array(
            'status' => 'failed',
            'resultreference' => json_encode(array('issues' => $issues)),
            'datecompleted' => date('Y-m-d H:i:s')
        ));
    }
}
?>
