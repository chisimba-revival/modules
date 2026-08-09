<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('Direct access denied'); }

class faq26service extends ChisimbaObject
{
    protected $gateway;

    public function init()
    {
        $this->gateway = $this->getObject('db_faq26_items', 'faq26');
    }

    protected function escapeStr(string $value): string
    {
        if (method_exists($this->gateway, 'escape')) {
            return "'" . $this->gateway->escape($value) . "'";
        }
        if (isset($this->gateway->db) && method_exists($this->gateway->db, 'escapeSimple')) {
            return "'" . $this->gateway->db->escapeSimple($value) . "'";
        }
        return "'" . addslashes($value) . "'";
    }

    public function getFaqsForScope(string $scopeType, string $scopeId, bool $includeUnpublished = false): array
    {
        $cleanType = $this->escapeStr($scopeType);
        $cleanId   = $this->escapeStr($scopeId);

        $filter = "WHERE scope_type = {$cleanType} AND scope_id = {$cleanId}";
        if (!$includeUnpublished) {
            $filter .= " AND is_published = 1";
        }
        $filter .= " ORDER BY display_order ASC, date_created DESC";

        $results = $this->gateway->getAll($filter);
        return is_array($results) ? $results : array();
    }

    public function saveFaq(array $data): array
    {
        $question  = trim((string)($data['question'] ?? ''));
        $answer    = trim((string)($data['answer'] ?? ''));
        $scopeType = trim((string)($data['scope_type'] ?? 'global'));
        $scopeId   = trim((string)($data['scope_id'] ?? 'global'));
        $creatorId = trim((string)($data['creator_id'] ?? 'system'));

        if ($question === '' || $answer === '') {
            return array('ok' => false, 'code' => 'missing_fields');
        }

        $id  = !empty($data['id']) ? $data['id'] : bin2hex(random_bytes(16));
        $now = date('Y-m-d H:i:s');

        $record = array(
            'id'            => $id,
            'scope_type'    => $scopeType,
            'scope_id'      => $scopeId,
            'question'      => $question,
            'answer'        => $answer,
            'display_order' => (int)($data['display_order'] ?? 0),
            'is_published'  => (int)($data['is_published'] ?? 1),
            'creator_id'    => $creatorId,
            'date_modified' => $now
        );

        if (empty($data['id'])) {
            $record['date_created'] = $now;
            $ok = $this->gateway->insert($record);
        } else {
            $ok = $this->gateway->update('id', $id, $record);
        }

        return array('ok' => $ok !== false, 'id' => $id);
    }

    public function deleteFaq(string $id, string $scopeType, string $scopeId): bool
    {
        return $this->gateway->delete('id', $id) !== false;
    }
}
?>
