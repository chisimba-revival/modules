<?php
/** Atomic outline persistence shared by course blueprint consumers. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class dbcourseoutline extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_contextcontent_sections',$pearDb,$errorCallback); }

    public function contextSnapshot($code, $lock=false)
    {
        $rows=$this->selectPrepared('SELECT * FROM tbl_context WHERE contextcode=?'.($lock?' FOR UPDATE':''),[$code]);
        return $rows[0]??[];
    }

    /** Snapshot all authored outline rows; locking prevents replacing a concurrent committed edit. */
    public function snapshot($code, $lock=false)
    {
        $suffix = $lock ? ' FOR UPDATE' : '';
        $queries = [
            'modules'=>'SELECT * FROM tbl_contextmodules WHERE contextcode=? ORDER BY id',
            'blocks'=>'SELECT * FROM tbl_context_blocks WHERE contextcode=? ORDER BY id',
            'sections'=>'SELECT * FROM tbl_contextcontent_sections WHERE contextcode=? ORDER BY id',
            'placements'=>'SELECT * FROM tbl_contextcontent_chaptercontext WHERE contextcode=? ORDER BY id',
            'chapters'=>'SELECT c.* FROM tbl_contextcontent_chapters c WHERE c.id IN (SELECT chapterid FROM tbl_contextcontent_chaptercontext WHERE contextcode=?) ORDER BY c.id',
            'introductions'=>'SELECT c.* FROM tbl_contextcontent_chaptercontent c WHERE c.chapterid IN (SELECT chapterid FROM tbl_contextcontent_chaptercontext WHERE contextcode=?) ORDER BY c.id',
            'order'=>'SELECT * FROM tbl_contextcontent_order WHERE contextcode=? ORDER BY id',
            'titles'=>'SELECT t.* FROM tbl_contextcontent_titles t WHERE t.id IN (SELECT titleid FROM tbl_contextcontent_order WHERE contextcode=?) ORDER BY t.id',
            'pages'=>'SELECT p.* FROM tbl_contextcontent_pages p WHERE p.titleid IN (SELECT titleid FROM tbl_contextcontent_order WHERE contextcode=?) ORDER BY p.id'
        ];
        $snapshot = [];
        foreach ($queries as $key=>$sql) $snapshot[$key] = $this->selectPrepared($sql.$suffix, [$code]);
        return $snapshot;
    }

    /** Remove only rows whose complete snapshot has already been checked by the caller. */
    public function removeUntouched(array $snapshot)
    {
        $tables = ['order'=>'order','pages'=>'pages','titles'=>'titles','introductions'=>'chaptercontent',
            'placements'=>'chaptercontext','chapters'=>'chapters','sections'=>'sections'];
        foreach ($tables as $key=>$table) foreach ($snapshot[$key] as $row) {
            if ($this->executePrepared('DELETE FROM tbl_contextcontent_'.$table.' WHERE id=?', [$row['id']]) !== 1)
                throw new RuntimeException('Outline removal failed');
        }
    }

    /** Create a section or chapter without request-time schema work or search side effects. */
    public function createContainer($code, array $item, $actor, $position, $sectionId='')
    {
        $now = date('Y-m-d H:i:s');
        $base = ['creatorid'=>$actor,'datecreated'=>$now];
        if ($item['role'] === 'section') return $this->checkedInsert(array_merge($base,
            ['contextcode'=>$code,'title'=>$item['title'],'introduction'=>$this->plainHtml($item['description']),
             'sectionorder'=>$position,'visibility'=>'Y']), 'tbl_contextcontent_sections');
        $id = $this->checkedInsert($base, 'tbl_contextcontent_chapters');
        $this->checkedInsert(array_merge($base, ['chapterid'=>$id,'chaptertitle'=>$item['title'],
            'introduction'=>$this->plainHtml($item['description']),'language'=>'en','original'=>'Y']), 'tbl_contextcontent_chaptercontent');
        $this->checkedInsert(array_merge($base, $sectionId!==''?['sectionid'=>$sectionId]:[], ['contextcode'=>$code,'chapterid'=>$id,
            'chapterorder'=>$position,'visibility'=>'Y']), 'tbl_contextcontent_chaptercontext');
        return $id;
    }

    private function checkedInsert(array $row, $table)
    {
        $row['id']=bin2hex(random_bytes(16));
        $id = $this->insert($row, $table);
        if (!$id) throw new RuntimeException('Outline insertion failed');
        return $id;
    }

    /** Map text is plain text, never executable markup. */
    public function plainHtml($text)
    { return trim((string)$text) === '' ? '' : '<p>'.nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')).'</p>'; }
}
