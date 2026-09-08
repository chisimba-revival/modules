<?php
/** Durable identity-document details used for certificate evidence. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class registrationidentityservice extends dbTable
{
    private const TABLE_NAME = 'tbl_registration_service_identities';
    private const TYPES = array('south_african_id', 'passport', 'national_identity_document');

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName ?: self::TABLE_NAME, $pearDb, $errorCallback);
        $this->_db = $this->objEngine->getDbObj();
    }

    public function saveForUser($userId, $documentType, $documentNumber, $actorId)
    {
        $userId = $this->userId($userId);
        $actorId = $this->userId($actorId);
        $documentType = $this->documentType($documentType);
        $documentNumber = $this->documentNumber($documentNumber);
        if ($userId === null || $actorId === null || $documentType === null || $documentNumber === null) {
            return array('ok' => false, 'code' => 'invalid_identity_document');
        }
        $now = date('Y-m-d H:i:s');
        $values = array(
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'document_fingerprint' => $this->fingerprint($documentNumber),
            'updated_at' => $now,
            'updated_by' => $actorId,
        );
        $existing = $this->forUser($userId);
        if ($existing) {
            $ok = $this->update('id', $existing['id'], $values);
            return array('ok' => $ok !== false, 'code' => $ok !== false ? 'identity_updated' : 'identity_save_failed');
        }
        $values += array('id' => bin2hex(random_bytes(16)), 'user_id' => $userId, 'created_at' => $now);
        $ok = $this->insert($values);
        return array('ok' => $ok !== false, 'code' => $ok !== false ? 'identity_saved' : 'identity_save_failed');
    }

    public function forUser($userId)
    {
        $userId = $this->userId($userId);
        if ($userId === null) { return false; }
        $rows = $this->getAll('WHERE user_id = ' . $this->_db->quote($userId) . ' LIMIT 1');
        return is_array($rows) && count($rows) === 1 ? $rows[0] : false;
    }

    public function findByDocumentNumber($documentNumber)
    {
        $documentNumber = $this->documentNumber($documentNumber);
        if ($documentNumber === null) { return array(); }
        $rows = $this->getAll('WHERE document_fingerprint = ' . $this->_db->quote($this->fingerprint($documentNumber)));
        return is_array($rows) ? $rows : array();
    }

    public function allowedTypes() { return self::TYPES; }
    private function fingerprint($number) { return hash('sha256', $this->normaliseNumber($number)); }
    private function normaliseNumber($number) { return strtoupper(preg_replace('/[^\pL\pN]/u', '', (string) $number)); }
    private function userId($value) { $value=is_scalar($value)?trim((string)$value):'';return $value!==''&&strlen($value)<=25&&!preg_match('/[\x00-\x1F\x7F]/',$value)?$value:null; }
    private function documentType($value) { $value=is_scalar($value)?strtolower(trim((string)$value)):'';return in_array($value,self::TYPES,true)?$value:null; }
    private function documentNumber($value) { $value=is_scalar($value)?trim((string)$value):'';return $value!==''&&strlen($value)<=128&&!preg_match('/[\x00-\x1F\x7F]/',$value)?$value:null; }
}
?>
