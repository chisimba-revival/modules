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

    public function findForPlan($planId, $itemId)
    {
        $filter = "WHERE plan_id='".addslashes($planId)."' AND id='".addslashes($itemId)."' LIMIT 1";
        $rows = $this->getAll($filter);
        return is_array($rows) && !empty($rows) ? $rows[0] : false;
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

    public function removeItem($planId, $itemId)
    {
        $item = $this->findForPlan($planId, $itemId);
        return $item ? $this->delete('id', $item['id']) : false;
    }

    public function saveWeight($planId, $itemId, $weight)
    {
        $item = $this->findForPlan($planId, $itemId);
        if (!$item) {
            return false;
        }
        return $this->update('id', $item['id'], array(
            'weight' => number_format((float) $weight, 3, '.', ''),
            'date_updated' => date('Y-m-d H:i:s')
        ));
    }
}
?>
