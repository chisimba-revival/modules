<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class dbgradebookassessmentplanitems extends dbtable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_gradebook_assessment_plan_items');
    }

    public function findByActivity($planId, $providerKey, $activityId)
    {
        $filter = "WHERE plan_id='".addslashes($planId)."' AND provider_key='".addslashes($providerKey)."' AND activity_id='".addslashes($activityId)."' LIMIT 1";
        $rows = $this->getAll($filter);
        return is_array($rows) && !empty($rows) ? $rows[0] : false;
    }

    public function getForPlan($planId)
    {
        $rows = $this->getAll("WHERE plan_id='".addslashes($planId)."' ORDER BY sort_order, date_created");
        return is_array($rows) ? $rows : array();
    }

    public function addItem(array $item)
    {
        $now = date('Y-m-d H:i:s');
        $item['sort_order'] = count($this->getForPlan($item['plan_id'])) + 1;
        $item['status'] = 'active';
        $item['date_created'] = $now;
        $item['date_updated'] = $now;
        return $this->insert($item);
    }
}
?>
