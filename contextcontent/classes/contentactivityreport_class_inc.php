<?php
/** Scoped, read-only activity reports. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class contentactivityreport extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_contextcontent_activitystreamer');
    }

    public function filters(array $input): array
    {
        $out = [];
        foreach (['startdate' => date('Y-m-01'), 'enddate' => date('Y-m-d')] as $key => $default) {
            $value = (string) ($input[$key] ?? $default);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Invalid report date');
            $out[$key] = $value;
        }
        if ($out['startdate'] > $out['enddate']) throw new InvalidArgumentException('Invalid report date range');
        $out['studentsonly'] = (string) ($input['studentsonly'] ?? '1') === '1';
        $out['q'] = mb_substr(trim((string) ($input['q'] ?? '')), 0, 100);
        $out['sort'] = in_array($input['sort'] ?? '', ['recent','oldest','name','accesses'], true) ? $input['sort'] : 'recent';
        $out['page'] = max(1, min(100000, (int) ($input['page'] ?? 1)));
        return $out;
    }

    public function report(string $context, array $filters, bool $summary, bool $export = false): array
    {
        $user = $this->getObject('user', 'security');
        $scope = $this->getObject('dbcontext', 'context');
        if ($context === '' || $context === 'root' || $context !== (string) $scope->getContextCode()
            || !$user->isLoggedIn() || !($user->isAdmin() || $this->getObject('managegroups', 'contextgroups')->isContextLecturer())) {
            throw new RuntimeException('Report access denied');
        }
        $where = 'a.contextcode = ? AND a.datecreated >= ? AND a.datecreated < ?';
        $parameters = [$context, $filters['startdate'].' 00:00:00', (new DateTimeImmutable($filters['enddate']))->modify('+1 day')->format('Y-m-d').' 00:00:00'];
        if ($filters['studentsonly']) {
            $groups = $this->getObject('groupservice', 'groupadmin');
            $id = $groups->groupIdForName($context.'^Students');
            $ids = $id === false ? [] : array_column($groups->getMembers($id), 'userId');
            if (!$ids) $where .= ' AND 1 = 0';
            else { $where .= ' AND a.userid IN ('.implode(',', array_fill(0, count($ids), '?')).')'; $parameters = array_merge($parameters, $ids); }
        }
        if ($filters['q'] !== '') {
            $where .= ' AND (u.username LIKE ? OR u.firstname LIKE ? OR u.surname LIKE ? OR a.description LIKE ?)';
            $parameters = array_merge($parameters, array_fill(0, 4, '%'.$filters['q'].'%'));
        }
        $from = ' FROM tbl_contextcontent_activitystreamer a LEFT JOIN tbl_users u ON u.userid = a.userid WHERE '.$where;
        $select = $summary
            ? "SELECT a.userid, MAX(u.username) AS username, MAX(CONCAT(COALESCE(u.firstname,''),' ',COALESCE(u.surname,''))) AS fullname, COUNT(*) AS accesses"
            : "SELECT a.id, a.userid, u.username, CONCAT(COALESCE(u.firstname,''),' ',COALESCE(u.surname,'')) AS fullname, a.contextitemid, a.pageorchapter, a.description, a.starttime, a.endtime, a.datecreated";
        $group = $summary ? ' GROUP BY a.userid' : '';
        $count = $this->selectPrepared('SELECT COUNT('.($summary ? 'DISTINCT a.userid' : '*').') AS total'.$from, $parameters);
        $total = (int) ($count[0]['total'] ?? 0);
        $page = min($filters['page'], max(1, (int) ceil($total / 30)));
        $order = $summary
            ? ($filters['sort'] === 'name' ? 'fullname ASC, a.userid' : 'accesses DESC, a.userid')
            : match ($filters['sort']) { 'oldest' => 'a.datecreated ASC, a.id', 'name' => 'fullname ASC, a.datecreated DESC, a.id', default => 'a.datecreated DESC, a.id' };
        $sql = $select.$from.$group.' ORDER BY '.$order;
        // CSV is streamed in bounded batches using the same authorised filters.
        $offset = $export ? max(0, (int) ($filters['offset'] ?? 0)) : ($page - 1) * 30;
        $rows = $this->selectPrepared($sql.' LIMIT '.($export ? 500 : 30).' OFFSET '.$offset, $parameters);
        if (!$summary) foreach ($rows as &$row) {
            $title = [];
            if ($row['pageorchapter'] === 'page') {
                $title = $this->selectPrepared("SELECT p.menutitle AS title FROM tbl_contextcontent_order o JOIN tbl_contextcontent_pages p ON p.titleid = o.titleid AND p.original = 'Y' WHERE o.id = ? AND o.contextcode = ? ORDER BY p.id LIMIT 1", [$row['contextitemid'], $context]);
            } elseif ($row['pageorchapter'] === 'chapter') {
                $title = $this->selectPrepared('SELECT c.chaptertitle AS title FROM tbl_contextcontent_chaptercontext cc JOIN tbl_contextcontent_chaptercontent c ON c.chapterid = cc.chapterid WHERE cc.chapterid = ? AND cc.contextcode = ? ORDER BY c.id LIMIT 1', [$row['contextitemid'], $context]);
            }
            $row['title'] = $title[0]['title'] ?? $row['contextitemid'];
        }
        return ['rows' => $rows, 'total' => $total, 'page' => $page];
    }

    public function csvCell($value): string
    {
        $value = (string) ($value ?? '');
        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
    }
}
