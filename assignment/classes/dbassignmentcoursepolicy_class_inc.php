<?php
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class dbassignmentcoursepolicy extends dbtable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_assignment_course_policy');
    }

    public function getPolicy($contextCode)
    {
        $row = $this->getRow('contextcode', (string) $contextCode);
        $policy = is_array($row) && isset($row['submission_policy'])
            ? (string) $row['submission_policy'] : 'single';
        return in_array($policy, array('single', 'until_closing', 'unlimited'), true)
            ? $policy : 'single';
    }

    public function setPolicy($contextCode, $policy)
    {
        $policy = in_array($policy, array('single', 'until_closing', 'unlimited'), true)
            ? $policy : 'single';
        $row = $this->getRow('contextcode', (string) $contextCode);
        $values = array('contextcode' => (string) $contextCode,
            'submission_policy' => $policy, 'updated' => date('Y-m-d H:i:s'));
        if (is_array($row) && !empty($row['id'])) {
            return $this->update('id', $row['id'], $values);
        }
        $values['id'] = md5(uniqid((string) $contextCode, true));
        return $this->insert($values);
    }
}
?>
