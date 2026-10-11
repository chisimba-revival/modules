<?php
/** Shop persistence. All inventory/order mutations serialize on the settings row.
 * This deliberately favours correctness for small catalogues over parallel writers.
 * @author Derek Keats <derek@dkeats.com>
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
require_once __DIR__ . '/shoprules.php';
class shopstore extends dbTable
{
    private const TABLES = ['books', 'orders', 'settings', 'history'];
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    { parent::init('tbl_shop_books', $pearDb, $errorCallback); }
    private function table($table)
    { if (!in_array($table, self::TABLES, true)) throw new InvalidArgumentException('Invalid shop table'); return 'tbl_shop_' . $table; }
    private function field($field)
    { if (!preg_match('/^[a-z_]+$/D', $field)) throw new InvalidArgumentException('Invalid field'); return $field; }

    public function rows($table,array $where=[],$limit=null) {
        $clauses=[];foreach($where as $field=>$value)$clauses[]=$this->field($field).'=?';
        $sql='SELECT * FROM '.$this->table($table).($clauses?' WHERE '.implode(' AND ',$clauses):'').($table==='books'?' ORDER BY display_order,id':' ORDER BY id');
        if($limit!==null)$sql.=' LIMIT '.max(1,min(500,(int)$limit));
        return $this->selectPrepared($sql,array_values($where));
    }
    public function one($table, $id) { return $this->rows($table, ['id' => $id], 1)[0] ?? null; }
    public function add($table,array $row) {
        $fields=array_keys($row);foreach($fields as $field)if(!preg_match('/^[a-z_]+$/D',$field))throw new InvalidArgumentException('Invalid field');
        $this->executeExactPrepared('INSERT INTO '.$this->table($table).' ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')',array_map(static fn($value)=>(string)$value,array_values($row)));return $row;
    }
    public function save($table,$id,array $changes) {
        $pairs=[];foreach($changes as $field=>$value){if(!preg_match('/^[a-z_]+$/D',$field))throw new InvalidArgumentException('Invalid field');$pairs[]=$field.'=?';}
        $this->executeExactPrepared('UPDATE '.$this->table($table).' SET '.implode(',',$pairs).' WHERE id=?',array_merge(array_map(static fn($value)=>(string)$value,array_values($changes)),[$id]));
    }

    public function transaction(callable $work) {
        return parent::transaction(function()use($work){
            $lock=$this->selectPrepared("SELECT id FROM tbl_shop_settings WHERE id='shop' FOR UPDATE");
            if(count($lock)!==1)throw new RuntimeException('Shop installation incomplete');
            return $work();
        });
    }
    public function reserved($bookId, $now, $except = '')
    {
        $sql = "SELECT id, snapshot FROM tbl_shop_orders WHERE payment_state='unpaid' AND fulfilment_state='held' AND hold_until>" . (int)$now;
        $rows = $this->selectPrepared($sql);
        if (!is_array($rows)) throw new RuntimeException('Shop reservation read failed');
        $quantity = 0;
        foreach ($rows as $order) {
            if ($order['id'] === $except) continue;
            foreach (ShopRules::inventory(json_decode($order['snapshot'], true, 512, JSON_THROW_ON_ERROR)) as $line) {
                if ($line['book_id'] === $bookId) $quantity += (int)$line['quantity'];
            }
        }
        return $quantity;
    }
    public function recentOrders()
    {
        $rows = $this->selectPrepared('SELECT * FROM tbl_shop_orders ORDER BY created_at DESC, id DESC LIMIT 200');
        if (!is_array($rows)) throw new RuntimeException('Shop order read failed');
        return $rows;
    }
}
