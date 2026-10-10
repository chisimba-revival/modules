<?php
/** Transactional event persistence. Every inventory mutation locks its occurrence first.
 * @author Derek Keats <derek@dkeats.com>
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class eventstore extends dbTable
{
    private const TABLES = ['events','occurrences','bookings','tickets','reviews','waitlist'];
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_events_events',$pearDb,$errorCallback); }
    private function table($name)
    { if (!in_array($name,self::TABLES,true)) throw new InvalidArgumentException('Unknown table'); return 'tbl_events_'.$name; }
    private function quote($value)
    { return (string)$value==='' ? "''" : $this->objEngine->getDbObj()->quote((string)$value,'text'); }
    public function rows($table,array $where=[], $lock=false)
    {
        $clauses=[];
        foreach($where as $field=>$value) {
            if(!preg_match('/^[a-z_]+$/D',$field)) throw new InvalidArgumentException('Invalid field');
            $clauses[]=$field.'='.$this->quote($value);
        }
        $sql='SELECT * FROM '.$this->table($table).($clauses?' WHERE '.implode(' AND ',$clauses):'').' ORDER BY id'.($lock?' FOR UPDATE':'');
        $rows=$this->objEngine->getDbObj()->queryAll($sql,null,MDB2_FETCHMODE_ASSOC);
        if(!is_array($rows)) throw new RuntimeException('Event read failed');
        return $rows;
    }
    public function one($table,$id,$lock=false)
    { if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id)) return null; return $this->rows($table,['id'=>$id],$lock)[0]??null; }
    public function add($table,array $row)
    { $this->write('INSERT INTO '.$this->table($table).' ('.implode(',',array_keys($row)).') VALUES ('.implode(',',array_map(fn($v)=>$this->quote($v),array_values($row))).')'); return $row; }
    public function save($table,$id,array $changes)
    { $pairs=[]; foreach($changes as $key=>$value) { if(!preg_match('/^[a-z_]+$/D',$key)) throw new InvalidArgumentException('Invalid field'); $pairs[]=$key.'='.$this->quote($value); } $this->write('UPDATE '.$this->table($table).' SET '.implode(',',$pairs).' WHERE id='.$this->quote($id)); }
    private function write($sql)
    { $result=$this->objEngine->getDbObj()->exec($sql); if($result===false||PEAR::isError($result)) throw new RuntimeException('Event write failed: '.(PEAR::isError($result)?$result->getMessage():'database error')); }
    public function transaction(callable $work)
    {
        // Refuse a non-transactional connection; a silent fallback can oversell.
        $db=$this->objEngine->getDbObj();
        if(!$db->supports('transactions')) throw new RuntimeException('Events requires transactions');
        if(!empty($db->in_transaction)) throw new RuntimeException('Nested event transaction is not supported');
        $started=$db->beginTransaction();
        if($started===false||PEAR::isError($started)) throw new RuntimeException('Event transaction could not start');
        try {
            $result=$work(); $committed=$db->commit();
            if($committed===false||PEAR::isError($committed)) throw new RuntimeException('Event transaction could not commit');
            return $result;
        } catch(Throwable $e) { $db->rollback(); throw $e; }
    }
    public function occupancy($id,$now,$except=null)
    {
        $count=0;
        foreach($this->rows('bookings',['occurrence_id'=>$id]) as $b) {
            if($b['id']===$except) continue;
            if($b['state']==='confirmed'||($b['state']==='held'&&(int)$b['expires_at']>$now)) $count+=(int)$b['quantity'];
        }
        foreach($this->rows('waitlist',['occurrence_id'=>$id,'state'=>'offered']) as $w)
            if((int)$w['expires_at']>$now) ++$count;
        return $count;
    }
}
