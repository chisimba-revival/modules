<?php
/** Private, scoped activity browsing through dbTable. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class activitybrowser extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_activity');
    }
    public function contextCode(): string
    {
        return (string) $this->getObject('dbcontext','context')->getContextCode();
    }
    public function mayView(string $mode): bool
    {
        if (!in_array($mode, ['site','context','pages'], true)) return false;
        $user = $this->getObject('user','security');
        if (!$user->isLoggedIn()) return false;
        if ($mode === 'site') return (bool) $user->isAdmin();
        $context = $this->contextCode();
        return $context !== '' && $context !== 'root'
            && ($user->isAdmin() || $this->getObject('managegroups','contextgroups')->isContextLecturer());
    }
    /** Authorise before counts, names or titles are queried, including block callers. */
    public function browse(string $mode, array $filters = [], int $size = 30): array
    {
        if (!$this->mayView($mode)) throw new RuntimeException('Activity access denied');
        $size = max(1, min(100, $size));
        $q = mb_substr(trim(is_string($filters['q'] ?? null) ? $filters['q'] : ''), 0, 100);
        $sort = in_array($filters['sort'] ?? '', ['recent','oldest','title'], true) ? $filters['sort'] : 'recent';
        $params = [];
        if ($mode === 'pages') {
            $from = " FROM tbl_contextcontent_order a JOIN tbl_contextcontent_pages p ON p.titleid=a.titleid AND p.original='Y'
                LEFT JOIN tbl_users u ON u.userid=COALESCE(NULLIF(p.modifierid,''),p.creatorid)";
            $where = ' WHERE a.contextcode=?'; $params[] = $this->contextCode();
            $title = 'p.menutitle'; $date = 'COALESCE(p.datemodified,p.datecreated)';
            $select = "a.id, p.menutitle AS title, '' AS description, a.contextcode, 'contextcontent' AS module, $date AS createdon";
            if ($q !== '') { $where .= ' AND p.menutitle LIKE ?'; $params[] = '%'.$q.'%'; }
        } else {
            $from = ' FROM tbl_activity a LEFT JOIN tbl_users u ON u.userid=a.createdby';
            $where = $mode === 'context' ? ' WHERE a.contextcode=?' : ' WHERE 1=1';
            if ($mode === 'context') $params[] = $this->contextCode();
            $title = 'a.title'; $date = 'a.createdon';
            $select = 'a.id,a.title,a.description,a.contextcode,a.module,a.createdon';
            if ($q !== '') { $where .= ' AND (a.title LIKE ? OR a.description LIKE ?)'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
        }
        $total = (int) $this->selectPrepared('SELECT COUNT(*) AS total'.$from.$where, $params)[0]['total'];
        $page = max(1, min((int) ($filters['page'] ?? 1), max(1, (int) ceil($total / $size))));
        $order = match ($sort) { 'oldest' => "$date ASC, a.id ASC", 'title' => "$title ASC, a.id ASC", default => "$date DESC, a.id DESC" };
        $rows = $this->selectPrepared("SELECT $select, CONCAT(COALESCE(u.firstname,''),' ',COALESCE(u.surname,'')) AS createdby".$from.$where
            .' ORDER BY '.$order.' LIMIT '.$size.' OFFSET '.(($page-1)*$size), $params);
        return ['rows'=>$rows,'total'=>$total,'page'=>$page,'size'=>$size,'q'=>$q,'sort'=>$sort,'mode'=>$mode];
    }
}
