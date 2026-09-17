<?php
/** Exercise the Gradebook repair hook without changing a live database. */
$GLOBALS['kewl_entry_point_run'] = true;
define('MDB2_OK', 1);
class dbtable
{
    public $admin;
    public function getObject($name, $module) { return $this->admin; }
}
class GradebookSchemaAdmin
{
    public $tables = array('tbl_gradebook_assessment_plan_items');
    public $columns = array('id', 'name');
    public $calls = array();
    public $result = MDB2_OK;
    public function listDbTables() { return $this->tables; }
    public function listTblFields($table) { return $this->columns; }
    public function alterTable($table, $changes, $check)
    {
        $this->calls[] = array($table, $changes, $check);
        if (!$check && $this->result === MDB2_OK) { $this->columns[] = 'short_name'; }
        return $this->result;
    }
}
require dirname(__DIR__).'/patches/installscripts_class_inc.php';
function expect($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
    echo 'PASS: '.$message.PHP_EOL;
}
$hook = new gradebook_installscripts();
$hook->admin = new GradebookSchemaAdmin();
$hook->preinstall('1.473');
expect(count($hook->admin->calls) === 2, 'Missing column is checked and added');
$definition = $hook->admin->calls[1][1]['add']['short_name'];
expect($definition === array('type'=>'text', 'length'=>16, 'notnull'=>true, 'default'=>''), 'Existing rows receive the fresh-install empty default');
$hook->preinstall('1.473');
expect(count($hook->admin->calls) === 2, 'Retry leaves existing short names untouched');
$hook->admin = new GradebookSchemaAdmin();
$hook->admin->tables = array();
$hook->preinstall();
expect($hook->admin->calls === array(), 'Fresh installation defers to table creation');
foreach (array('tables', 'columns', 'result') as $failure) {
    $hook->admin = new GradebookSchemaAdmin();
    $hook->admin->$failure = false;
    $thrown = false;
    try { $hook->preinstall('1.473'); } catch (RuntimeException $e) { $thrown = true; }
    expect($thrown, 'Failure of '.$failure.' stops the upgrade');
}
