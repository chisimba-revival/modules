<?php
/** Reusable certificate configuration and immutable issuance boundary. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class certificateservice extends dbTable
{
    private const BASES = 'tbl_certificate_service_bases';
    private const SIGNERS = 'tbl_certificate_service_signers';
    private const ASSIGNMENTS = 'tbl_certificate_service_assignments';
    private const ISSUANCES = 'tbl_certificate_service_issuances';

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName ?: self::BASES, $pearDb, $errorCallback);
        $this->_db = $this->objEngine->getDbObj();
        $this->events = $this->getObject('accounteventservice', 'account-event-service');
        $this->config = $this->getObject('altconfig', 'config');
    }

    public function createBase(array $input, $actorId)
    {
        $values = array(
            'id' => bin2hex(random_bytes(16)),
            'name' => $this->text($input['name'] ?? '', 191),
            'organisation' => $this->text($input['organisation'] ?? '', 191),
            'company_name' => $this->text($input['companyName'] ?? '', 255),
            'company_location' => $this->optionalText($input['companyLocation'] ?? '', 255),
            'website_url' => $this->url($input['websiteUrl'] ?? ''),
            'primary_colour' => $this->colour($input['primaryColour'] ?? '#1f2937'),
            'accent_colour' => $this->colour($input['accentColour'] ?? '#b49352'),
            'logo_path' => null,
            'status' => 'active', 'created_by' => $this->text($actorId, 25),
            'date_created' => date('Y-m-d H:i:s'), 'date_updated' => date('Y-m-d H:i:s')
        );
        if (in_array(null, array($values['name'], $values['organisation'], $values['company_name'], $values['primary_colour'], $values['accent_colour'], $values['created_by']), true)) {
            return array('ok' => false, 'code' => 'invalid_base');
        }
        return $this->insertInto(self::BASES, $values)
            ? array('ok' => true, 'code' => 'base_created', 'id' => $values['id'])
            : array('ok' => false, 'code' => 'base_create_failed');
    }

    public function createSigner(array $input, $actorId)
    {
        $values = array(
            'id' => bin2hex(random_bytes(16)), 'name' => $this->text($input['name'] ?? '', 191),
            'title' => $this->optionalText($input['title'] ?? '', 191), 'signature_path' => null,
            'status' => 'active', 'created_by' => $this->text($actorId, 25),
            'date_created' => date('Y-m-d H:i:s'), 'date_updated' => date('Y-m-d H:i:s')
        );
        if ($values['name'] === null || $values['created_by'] === null) {
            return array('ok' => false, 'code' => 'invalid_signer');
        }
        return $this->insertInto(self::SIGNERS, $values)
            ? array('ok' => true, 'code' => 'signer_created', 'id' => $values['id'])
            : array('ok' => false, 'code' => 'signer_create_failed');
    }

    public function updateBase($id, array $input)
    {
        $existing=$this->find(self::BASES,$id);if(!$existing){return array('ok'=>false,'code'=>'base_not_found');}
        $values=array('name'=>$this->text($input['name']??'',191),'organisation'=>$this->text($input['organisation']??'',191),'company_name'=>$this->text($input['companyName']??'',255),'company_location'=>$this->optionalText($input['companyLocation']??'',255),'website_url'=>$this->url($input['websiteUrl']??''),'primary_colour'=>$this->colour($input['primaryColour']??''),'accent_colour'=>$this->colour($input['accentColour']??''),'date_updated'=>date('Y-m-d H:i:s'));
        if(in_array(null,array($values['name'],$values['organisation'],$values['company_name'],$values['primary_colour'],$values['accent_colour']),true)){return array('ok'=>false,'code'=>'invalid_base');}
        $ok=$this->updateIn(self::BASES,$existing['id'],$values);return array('ok'=>(bool)$ok,'code'=>$ok?'base_updated':'base_update_failed','id'=>$existing['id']);
    }

    public function updateSigner($id, array $input)
    {
        $existing=$this->find(self::SIGNERS,$id);if(!$existing){return array('ok'=>false,'code'=>'signer_not_found');}
        $values=array('name'=>$this->text($input['name']??'',191),'title'=>$this->optionalText($input['title']??'',191),'date_updated'=>date('Y-m-d H:i:s'));
        if($values['name']===null){return array('ok'=>false,'code'=>'invalid_signer');}
        $ok=$this->updateIn(self::SIGNERS,$existing['id'],$values);return array('ok'=>(bool)$ok,'code'=>$ok?'signer_updated':'signer_update_failed','id'=>$existing['id']);
    }

    public function assign($resourceType, $resourceId, $baseId, $signerId, $actorId)
    {
        $resourceType = $this->identifier($resourceType, 64);
        $resourceId = $this->text($resourceId, 191);
        $base = $this->find(self::BASES, $baseId);
        $signer = $this->find(self::SIGNERS, $signerId);
        $actorId = $this->text($actorId, 25);
        if ($resourceType === null || $resourceId === null || !$base || !$signer || $actorId === null) {
            return array('ok' => false, 'code' => 'invalid_assignment');
        }
        $existing = $this->assignmentFor($resourceType, $resourceId);
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $saved = $this->updateIn(self::ASSIGNMENTS, $existing['id'], array(
                'base_id' => $baseId, 'signer_id' => $signerId, 'status' => 'active', 'date_updated' => $now
            ));
            return array('ok' => (bool) $saved, 'code' => $saved ? 'assignment_updated' : 'assignment_failed', 'id' => $existing['id']);
        }
        $id = bin2hex(random_bytes(16));
        $saved = $this->insertInto(self::ASSIGNMENTS, array(
            'id' => $id, 'resource_type' => $resourceType, 'resource_id' => $resourceId,
            'base_id' => $baseId, 'signer_id' => $signerId, 'status' => 'active',
            'created_by' => $actorId, 'date_created' => $now, 'date_updated' => $now
        ));
        return array('ok' => (bool) $saved, 'code' => $saved ? 'assignment_created' : 'assignment_failed', 'id' => $id);
    }

    public function issue(array $claim)
    {
        $assignment = $this->assignmentFor($claim['resourceType'] ?? '', $claim['resourceId'] ?? '');
        $userId = $this->text($claim['userId'] ?? '', 25);
        $completion = $this->text($claim['completionReference'] ?? '', 191);
        $recipient = $this->text($claim['recipientName'] ?? '', 255);
        $resourceTitle = $this->text($claim['resourceTitle'] ?? '', 255);
        if (!$assignment || $userId === null || $completion === null || $recipient === null || $resourceTitle === null || empty($claim['eligible'])) {
            return array('ok' => false, 'code' => 'not_eligible');
        }
        $existing = $this->issuanceFor($assignment['id'], $userId, $completion);
        if ($existing) { return array('ok' => true, 'code' => 'already_issued', 'issuance' => $existing); }
        $base = $this->find(self::BASES, $assignment['base_id']);
        $signer = $this->find(self::SIGNERS, $assignment['signer_id']);
        if (!$base || !$signer) { return array('ok' => false, 'code' => 'configuration_missing'); }
        $issuedAt = date('Y-m-d H:i:s');
        $id = bin2hex(random_bytes(16));
        $snapshot = array(
            'recipient_name' => $recipient, 'resource_title' => $resourceTitle,
            'completed_at' => (string) ($claim['completedAt'] ?? $issuedAt),
            'base' => $base, 'signer' => $signer
        );
        $row = array(
            'id' => $id, 'certificate_number' => strtoupper(substr($id, 0, 8).'-'.substr($id, 8, 8)),
            'assignment_id' => $assignment['id'], 'subject_user_id' => $userId,
            'resource_type' => $assignment['resource_type'], 'resource_id' => $assignment['resource_id'],
            'completion_reference' => $completion,
            'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'issued_at' => $issuedAt, 'issued_by_type' => 'service', 'issued_by_id' => 'certificate-service'
        );
        $this->beginTransaction();
        if (!$this->insertInto(self::ISSUANCES, $row)) { $this->rollbackTransaction(); return array('ok' => false, 'code' => 'issue_failed'); }
        $event=$this->events->append(array(
            'eventType' => 'certificate.issued', 'subjectType' => 'user', 'subjectId' => $userId,
            'actorType' => 'service', 'actorId' => 'certificate-service', 'outcome' => 'succeeded',
            'correlationId' => 'certificate.issue.'.$id, 'sourceService' => 'certificate-service',
            'metadata' => array('certificate_number' => $row['certificate_number'], 'resource_type' => $row['resource_type'], 'resource_id' => $row['resource_id'])
        ));
        if(empty($event['ok'])){$this->rollbackTransaction();return array('ok'=>false,'code'=>'issue_audit_failed');}
        $this->commitTransaction();
        $row['snapshot'] = $snapshot;
        return array('ok' => true, 'code' => 'issued', 'issuance' => $row);
    }

    public function activeBases() { return $this->rows(self::BASES); }
    public function activeSigners() { return $this->rows(self::SIGNERS); }
    public function storeImageAsset(array $upload, $kind, $recordId)
    {
        $map=array('logo'=>array(self::BASES,'logo_path'),'signature'=>array(self::SIGNERS,'signature_path'));
        if(!isset($map[$kind])||!$this->find($map[$kind][0],$recordId)||($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||empty($upload['tmp_name'])||!is_uploaded_file($upload['tmp_name'])||($upload['size']??0)>2097152){return false;}
        $info=@getimagesize($upload['tmp_name']);$extensions=array('image/png'=>'png','image/jpeg'=>'jpg');if(!is_array($info)||!isset($extensions[$info['mime']])){return false;}
        $directory=rtrim($this->config->getcontentBasePath(),'/').'/certificate-service';if(!is_dir($directory)&&!mkdir($directory,0750,true)&&!is_dir($directory)){return false;}
        $path=$directory.'/'.$kind.'-'.$recordId.'.'.$extensions[$info['mime']];if(!move_uploaded_file($upload['tmp_name'],$path)){return false;}@chmod($path,0640);
        return $this->updateIn($map[$kind][0],$recordId,array($map[$kind][1]=>$path,'date_updated'=>date('Y-m-d H:i:s')));
    }
    public function assignmentFor($type, $id)
    {
        $type = $this->identifier($type, 64); $id = $this->text($id, 191);
        if ($type === null || $id === null) { return false; }
        return $this->one(self::ASSIGNMENTS, "resource_type=".$this->quote($type)." AND resource_id=".$this->quote($id)." AND status='active'");
    }
    public function issuanceById($id) { return $this->find(self::ISSUANCES, $id); }

    private function issuanceFor($assignment, $user, $completion)
    { return $this->one(self::ISSUANCES, 'assignment_id='.$this->quote($assignment).' AND subject_user_id='.$this->quote($user).' AND completion_reference='.$this->quote($completion)); }
    private function rows($table)
    { $old=$this->_tableName; $this->_tableName=$table; $rows=$this->getAll("WHERE status='active' ORDER BY name"); $this->_tableName=$old; return is_array($rows)?$rows:array(); }
    private function find($table, $id)
    { $id=$this->text($id,32); return $id===null?false:$this->one($table, 'id='.$this->quote($id)); }
    private function one($table, $where)
    { $old=$this->_tableName; $this->_tableName=$table; $rows=$this->getAll('WHERE '.$where.' LIMIT 1'); $this->_tableName=$old; return is_array($rows)&&!empty($rows)?$rows[0]:false; }
    private function insertInto($table, array $values)
    { $old=$this->_tableName; $this->_tableName=$table; $ok=$this->insert($values); $this->_tableName=$old; return $ok!==false; }
    private function updateIn($table, $id, array $values)
    { $old=$this->_tableName; $this->_tableName=$table; $ok=$this->update('id',$id,$values); $this->_tableName=$old; return $ok!==false; }
    private function quote($value) { return $this->_db->quote((string)$value); }
    private function identifier($value,$max) { $value=is_scalar($value)?trim((string)$value):''; return $value!==''&&strlen($value)<=$max&&preg_match('/^[a-z][a-z0-9_.:-]*$/i',$value)?$value:null; }
    private function text($value,$max) { $value=is_scalar($value)?trim((string)$value):''; return $value!==''&&strlen($value)<=$max&&!preg_match('/[\x00-\x1F\x7F]/',$value)?$value:null; }
    private function optionalText($value,$max) { $value=is_scalar($value)?trim((string)$value):''; return $value===''?null:$this->text($value,$max); }
    private function colour($value) { $value=is_scalar($value)?trim((string)$value):''; return preg_match('/^#[0-9a-fA-F]{6}$/',$value)?strtolower($value):null; }
    private function url($value) { $value=is_scalar($value)?trim((string)$value):''; return $value===''?null:(filter_var($value,FILTER_VALIDATE_URL)&&preg_match('#^https?://#i',$value)?$value:null); }
}
?>
