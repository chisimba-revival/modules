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

    public function rows($table,array $where=[],$lock=false) {
        $clauses=[];foreach($where as $field=>$value){if(!preg_match('/^[a-z_]+$/D',$field))throw new InvalidArgumentException('Invalid field');$clauses[]=$field.'=?';}
        return $this->selectPrepared('SELECT * FROM '.$this->table($table).($clauses?' WHERE '.implode(' AND ',$clauses):'').' ORDER BY id'.($lock?' FOR UPDATE':''),array_values($where));
    }
    public function one($table,$id,$lock=false)
    { if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id)) return null; return $this->rows($table,['id'=>$id],$lock)[0]??null; }
    public function add($table,array $row) {
        $fields=array_keys($row);foreach($fields as $field)if(!preg_match('/^[a-z_]+$/D',$field))throw new InvalidArgumentException('Invalid field');
        $this->executeExactPrepared('INSERT INTO '.$this->table($table).' ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')',array_map(static fn($value)=>(string)$value,array_values($row)));return $row;
    }
    public function save($table,$id,array $changes) {
        $pairs=[];foreach($changes as $field=>$value){if(!preg_match('/^[a-z_]+$/D',$field))throw new InvalidArgumentException('Invalid field');$pairs[]=$field.'=?';}
        $this->executeExactPrepared('UPDATE '.$this->table($table).' SET '.implode(',',$pairs).' WHERE id=?',array_merge(array_map(static fn($value)=>(string)$value,array_values($changes)),[$id]));
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
