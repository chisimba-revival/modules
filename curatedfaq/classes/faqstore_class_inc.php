<?php
/** Storage only; web callers use faqservice for every operation. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class faqstore extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback')
    {
        parent::init('tbl_curatedfaq_items', $pearDb, $errorCallback);
    }

    // Atomic editing uses the inherited checked dbTable transaction boundary.
    public function item($id, $lock = false)
    {
        $rows = $this->selectPrepared(
            'SELECT * FROM tbl_curatedfaq_items WHERE id=?' . ($lock ? ' FOR UPDATE' : ''),
            [$id]
        );
        return $rows[0] ?? null;
    }

    public function listing($manage = false, $search = '', $category = '', $page = 1)
    {
        $sql = 'SELECT f.* FROM tbl_curatedfaq_items f WHERE '
            . ($manage ? '1=1' : "f.status='published'");
        $parameters = [];
        if ($search !== '') {
            $sql .= ' AND (f.question LIKE ? OR f.answer LIKE ?)';
            $parameters[] = '%' . $search . '%';
            $parameters[] = '%' . $search . '%';
        }
        if ($category !== '') {
            $sql .= " AND EXISTS (SELECT 1 FROM tbl_classification_links l WHERE l.module_id='curatedfaq' AND l.item_id=f.id AND l.term_id=?)";
            $parameters[] = $category;
        }
        $offset = (max(1, min(10000, (int) $page)) - 1) * 20;
        return $this->selectPrepared(
            $sql . ' ORDER BY f.display_order,f.question,f.id LIMIT 21 OFFSET ' . $offset,
            $parameters
        );
    }

    public function visibleCategories()
    {
        return $this->selectPrepared("SELECT DISTINCT t.id,t.name FROM tbl_classification_terms t JOIN tbl_classification_links l ON l.term_id=t.id JOIN tbl_curatedfaq_items f ON f.id=l.item_id WHERE l.module_id='curatedfaq' AND f.status='published' ORDER BY t.name");
    }

    public function persist(array $row, $exists)
    {
        $id = $row['id'];
        unset($row['id']);
        $result = $exists ? $this->update('id', $id, $row) : $this->insert(['id' => $id] + $row);
        if ($result === false) throw new RuntimeException('failed');
        $saved = $this->item($id);
        if (!$saved) throw new RuntimeException('failed');
        return $saved;
    }
}
