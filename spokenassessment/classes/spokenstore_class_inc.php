<?php
/** Durable activity/attempt store; serialised writes and explicit state transitions. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class spokenstore extends dbTable
{
    private $connection;
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_spoken_activities', $pearDb, $errorCallback);
        $this->connection=$this->objEngine->getDbObj();
        if (method_exists($this->connection,'setCharset')) $this->checked($this->connection->setCharset('utf8mb4'));
    }
    /** Avoid generic dbTable developer logging of private transcript values. */
    private function run($sql, $read = false)
    {
        $db=$this->connection;
        if (method_exists($db,'pushErrorHandling')) $db->pushErrorHandling(PEAR_ERROR_RETURN);
        try {
            if ($db instanceof PDO) {
                $result=$read?$db->query($sql):$db->exec($sql);
                $this->checked($result); return $read?$result->fetchAll(PDO::FETCH_ASSOC):$result;
            }
            return $this->checked($read?$db->queryAll($sql):$db->exec($sql));
        } finally { if (method_exists($db,'popErrorHandling')) $db->popErrorHandling(); }
    }
    private function rows($sql)
    {
        // MDB2's portability mode maps empty text to NULL on reads.
        $rows=$this->run($sql,true);
        foreach ($rows as &$row) foreach ($row as &$value) if ($value===null) $value='';
        return $rows;
    }
    private function write($table, array $values, $id = null)
    {
        foreach (array_keys($values) as $key) if (!preg_match('/^[a-z_]+$/D',$key)) throw new LogicException('invalid_column');
        if ($id===null) $sql='INSERT INTO '.$table.' ('.implode(',',array_keys($values)).') VALUES ('.implode(',',array_map([$this,'q'],array_values($values))).')';
        else { $parts=[]; foreach ($values as $key=>$value) $parts[]=$key.'='.$this->q($value); $sql='UPDATE '.$table.' SET '.implode(',',$parts).' WHERE id='.$this->q($id); }
        return $this->run($sql);
    }
    public function q($value)
    {
        $db = $this->objEngine->getDbObj();
        return method_exists($db, 'quoteSmart') ? $db->quoteSmart((string)$value) : $db->quote((string)$value);
    }
    public function now() { return $this->getObject('timeanddateservice', 'timeanddate-service')->nowStorage(); }
    public function transaction(callable $work)
    {
        $this->run('START TRANSACTION');
        try { $result = $work(); $this->run('COMMIT'); return $result; }
        catch (Throwable $e) { $this->run('ROLLBACK'); throw $e; }
    }
    public function checked($result)
    {
        if ($result === false || (is_object($result) && is_a($result, 'PEAR_Error'))) throw new RuntimeException('storage_failed');
        return $result;
    }
    public function activity($id, $lock = false)
    { $rows=$this->rows('SELECT * FROM tbl_spoken_activities WHERE id='.$this->q($id).($lock?' FOR UPDATE':'')); return $rows[0]??null; }
    public function attempt($id, $lock = false)
    { $rows=$this->rows('SELECT * FROM tbl_spoken_attempts WHERE id='.$this->q($id).($lock?' FOR UPDATE':'')); return $rows[0]??null; }
    public function activities($context, $teacher, $page = 1)
    {
        return $this->rows('SELECT * FROM tbl_spoken_activities WHERE contextcode='.$this->q($context)
            .($teacher?'':" AND published=1").' ORDER BY date_created DESC,id DESC LIMIT '.((max(1,min(10000,(int)$page))-1)*20).',21');
    }
    public function attempts($activity, $owner = null, $page = 1, $limit = 6)
    {
        return $this->rows('SELECT * FROM tbl_spoken_attempts WHERE activity_id='.$this->q($activity)
            .($owner===null?'':' AND userid='.$this->q($owner)).' ORDER BY date_created DESC,id DESC LIMIT '.((max(1,min(10000,(int)$page))-1)*20).','.max(1,min(21,(int)$limit))) ?: [];
    }
    public function putActivity($id, array $values)
    { $this->write('tbl_spoken_activities',$values,$id?:null); }
    public function addAttempt(array $values)
    { $this->write('tbl_spoken_attempts',$values); }
    public function changeAttempt($id, array $values)
    { $values['date_updated']=$this->now(); $this->write('tbl_spoken_attempts',$values,$id); }
    /** Claim under a row lock; two workers cannot issue the same provider request. */
    public function claim()
    {
        return $this->transaction(function () {
            $rows=$this->rows("SELECT * FROM tbl_spoken_attempts WHERE state IN ('queued_transcription','queued_feedback') ORDER BY date_created,id LIMIT 1 FOR UPDATE");
            if (!$rows) return null;
            $row=$rows[0]; $row['state']=$row['state']==='queued_transcription'?'transcribing':'feedback_processing';
            $row['claim_token']=bin2hex(random_bytes(16));
            $this->changeAttempt($row['id'], ['state'=>$row['state'],'claim_token'=>$row['claim_token'],'error_code'=>'']);
            return $row;
        });
    }
    /** A stale worker cannot overwrite an explicitly recovered attempt. */
    public function finish(array $claim, array $values)
    {
        return $this->transaction(function () use ($claim,$values) {
            $current=$this->attempt($claim['id'],true);
            if (!$current || $current['state']!==$claim['state'] || !hash_equals((string)$current['claim_token'],(string)$claim['claim_token'])) return false;
            $this->changeAttempt($claim['id'],array_merge($values,['claim_token'=>''])); return true;
        });
    }
}
