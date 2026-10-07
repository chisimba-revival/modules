<?php
/** Shop persistence. All inventory/order mutations serialize on the settings row.
 * This deliberately favours correctness for small catalogues over parallel writers.
 * @author Derek Keats <derek@dkeats.com>
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class shopstore extends dbTable
{
    private const TABLES = ['books', 'orders', 'settings', 'history'];
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    { parent::init('tbl_shop_books', $pearDb, $errorCallback); }
    private function table($table)
    { if (!in_array($table, self::TABLES, true)) throw new InvalidArgumentException('Invalid shop table'); return 'tbl_shop_' . $table; }
    private function field($field)
    { if (!preg_match('/^[a-z_]+$/D', $field)) throw new InvalidArgumentException('Invalid field'); return $field; }
    private function quote($value)
    { return $value === null ? 'NULL' : ((string)$value === '' ? "''" : $this->objEngine->getDbObj()->quote((string)$value, 'text')); }
    public function rows($table, array $where = [], $limit = null)
    {
        $clauses = [];
        foreach ($where as $field => $value) $clauses[] = $this->field($field) . '=' . $this->quote($value);
        $sql = 'SELECT * FROM ' . $this->table($table) . ($clauses ? ' WHERE ' . implode(' AND ', $clauses) : '') . ' ORDER BY id';
        if ($limit !== null) $sql .= ' LIMIT ' . max(1, min(500, (int)$limit));
        $result = $this->objEngine->getDbObj()->queryAll($sql, null, MDB2_FETCHMODE_ASSOC);
        if (!is_array($result)) throw new RuntimeException('Shop read failed');
        return $result;
    }
    public function one($table, $id) { return $this->rows($table, ['id' => $id], 1)[0] ?? null; }
    public function add($table, array $row)
    {
        $fields = array_map([$this, 'field'], array_keys($row));
        $this->write('INSERT INTO ' . $this->table($table) . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', array_map([$this, 'quote'], array_values($row))) . ')');
        return $row;
    }
    public function save($table, $id, array $changes)
    {
        $pairs = [];
        foreach ($changes as $field => $value) $pairs[] = $this->field($field) . '=' . $this->quote($value);
        $this->write('UPDATE ' . $this->table($table) . ' SET ' . implode(',', $pairs) . ' WHERE id=' . $this->quote($id));
    }
    private function write($sql)
    {
        $result = $this->objEngine->getDbObj()->exec($sql);
        if ($result === false || PEAR::isError($result)) throw new RuntimeException('Shop write failed');
    }
    public function transaction(callable $work)
    {
        $db = $this->objEngine->getDbObj();
        if (!$db->supports('transactions') || !empty($db->in_transaction)) throw new RuntimeException('Shop requires an independent transaction');
        $started = $db->beginTransaction();
        if ($started === false || PEAR::isError($started)) throw new RuntimeException('Shop transaction failed');
        try {
            $lock = $db->queryAll("SELECT id FROM tbl_shop_settings WHERE id='shop' FOR UPDATE", null, MDB2_FETCHMODE_ASSOC);
            if (!is_array($lock) || count($lock) !== 1) throw new RuntimeException('Shop installation incomplete');
            $result = $work();
            $committed = $db->commit();
            if ($committed === false || PEAR::isError($committed)) throw new RuntimeException('Shop commit failed');
            return $result;
        } catch (Throwable $error) { $db->rollback(); throw $error; }
    }
    public function reserved($bookId, $now, $except = '')
    {
        $sql = "SELECT id, snapshot FROM tbl_shop_orders WHERE payment_state='unpaid' AND fulfilment_state='held' AND hold_until>" . (int)$now;
        $rows = $this->objEngine->getDbObj()->queryAll($sql, null, MDB2_FETCHMODE_ASSOC);
        if (!is_array($rows)) throw new RuntimeException('Shop reservation read failed');
        $quantity = 0;
        foreach ($rows as $order) {
            if ($order['id'] === $except) continue;
            foreach (json_decode($order['snapshot'], true, 512, JSON_THROW_ON_ERROR)['lines'] as $line) {
                if ($line['book_id'] === $bookId) $quantity += (int)$line['quantity'];
            }
        }
        return $quantity;
    }
    public function recentOrders()
    {
        $rows = $this->objEngine->getDbObj()->queryAll('SELECT * FROM tbl_shop_orders ORDER BY created_at DESC, id DESC LIMIT 200', null, MDB2_FETCHMODE_ASSOC);
        if (!is_array($rows)) throw new RuntimeException('Shop order read failed');
        return $rows;
    }
}
