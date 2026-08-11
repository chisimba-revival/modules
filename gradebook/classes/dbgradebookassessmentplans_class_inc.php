<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class dbgradebookassessmentplans extends dbtable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_gradebook_assessment_plans');
    }

    public function ensureForContext($contextCode, $userId)
    {
        $plan = $this->findForContext($contextCode);
        if ($plan) { return $plan['id']; }
        $now = date('Y-m-d H:i:s');
        return $this->insert(array(
            'context_code' => $contextCode, 'status' => 'draft',
            'created_by' => $userId, 'date_created' => $now, 'date_updated' => $now
        ));
    }

    public function findForContext($contextCode)
    {
        $rows = $this->getAll("WHERE context_code='".addslashes($contextCode)."' LIMIT 1");
        return is_array($rows) && !empty($rows) ? $rows[0] : false;
    }
}
?>
