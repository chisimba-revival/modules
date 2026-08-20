<?php
if (!$GLOBALS['kewl_entry_point_run']) { die("You cannot view this page directly"); }

/** Read-only learning journey state owned by ContextContent. */
class learningjourney extends ChisimbaObject
{
    private $objOrder;
    private $objActivity;
    private $objBookmarks;

    public function init()
    {
        $this->objOrder = $this->getObject('db_contextcontent_order', 'contextcontent');
        $this->objActivity = $this->getObject('db_contextcontent_activitystreamer', 'contextcontent');
        $this->objBookmarks = $this->getObject('db_contextcontent_bookmarks', 'contextcontent');
    }

    public function getState($contextCode, $userId = '')
    {
        $state = array(
            'available'=>FALSE,
            'started'=>FALSE,
            'pageid'=>'',
            'pagetitle'=>'',
            'visited'=>0,
            'total'=>0,
            'bookmarks'=>array()
        );
        $firstPage = $this->objOrder->getFirstPage($contextCode);
        if ($firstPage === FALSE || empty($firstPage['id'])) return $state;

        $state['available'] = TRUE;
        $state['pageid'] = $firstPage['id'];
        $state['pagetitle'] = isset($firstPage['menutitle']) ? $firstPage['menutitle'] : '';
        $state['total'] = (int) $this->objOrder->getNumContextPages($contextCode);
        if ($userId === '' || $userId === NULL) return $state;

        foreach ($this->objBookmarks->idsForUser($contextCode, $userId) as $bookmarkId) {
            $bookmarkPage = $this->objOrder->getPage($bookmarkId, $contextCode);
            if ($bookmarkPage === FALSE || empty($bookmarkPage['id'])) {
                continue;
            }
            $state['bookmarks'][] = array(
                'pageid' => $bookmarkPage['id'],
                'pagetitle' => isset($bookmarkPage['menutitle']) ? $bookmarkPage['menutitle'] : ''
            );
        }

        $latest = $this->objActivity->getLatestPageVisit($userId, $contextCode);
        if ($latest === FALSE || empty($latest['contextitemid'])) return $state;

        $lastPage = $this->objOrder->getPage($latest['contextitemid'], $contextCode);
        if ($lastPage === FALSE || empty($lastPage['id'])) return $state;

        $state['started'] = TRUE;
        $state['pageid'] = $lastPage['id'];
        $state['pagetitle'] = isset($lastPage['menutitle']) ? $lastPage['menutitle'] : '';
        $state['visited'] = (int) $this->objActivity->countVisitedPages($userId, $contextCode);
        return $state;
    }
}
?>
