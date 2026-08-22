<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class ingestservice extends ChisimbaObject
{
    public function init()
    {
        $this->validator = $this->getObject('ingestvalidator', 'ingestservice');
        $this->docx = $this->getObject('docxingestparser', 'ingestservice');
        $this->odt = $this->getObject('odtingestparser', 'ingestservice');
        $this->runs = $this->getObject('db_ingestservice_runs', 'ingestservice');
        $this->sysconfig = $this->getObject('dbsysconfig', 'sysconfig');
    }

    public function parse($sourcePath, array $options = array())
    {
        $options += array(
            'unknownStylePolicy' => $this->sysconfig->getValue('INGESTSERVICE_UNKNOWN_STYLE_POLICY', 'ingestservice'),
            'maxImageBytes' => (int) $this->sysconfig->getValue('INGESTSERVICE_MAX_IMAGE_BYTES', 'ingestservice')
        );
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            return $this->failure('source.unreadable', 'The source file cannot be read.', 'source');
        }
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (!in_array($extension, array('docx', 'odt'), true)) {
            return $this->failure('source.unsupported_type', 'Only DOCX and ODT sources are supported by this release.', 'source');
        }
        try {
            $document = $this->{$extension}->parse($sourcePath, $options);
            $document['source'] = array(
                'type' => $extension,
                'name' => basename($sourcePath),
                'fingerprint' => hash_file('sha256', $sourcePath)
            );
            return $this->validator->validate($document);
        } catch (Throwable $error) {
            return $this->failure('source.parse_failed', $error->getMessage(), 'source');
        }
    }

    public function preview($sourcePath, array $options = array())
    {
        $document = $this->parse($sourcePath, $options);
        foreach (($document['assets'] ?? array()) as &$asset) {
            unset($asset['content']);
        }
        return $document;
    }

    public function deliver(array $document, $consumerModule, $consumerClass, array $options)
    {
        $document = $this->validator->validate($document);
        if (!$document['valid']) {
            return array('status' => 'rejected', 'issues' => $document['issues']);
        }
        $target = trim((string) ($options['target'] ?? ''));
        if ($target === '') {
            return $this->failure('delivery.missing_target', 'A consumer target is required.', 'delivery');
        }
        $consumer = $this->getObject($consumerClass, $consumerModule);
        $fingerprint = (string) ($document['source']['fingerprint'] ?? '');
        $completed = $this->runs->findCompleted($fingerprint, $consumerModule, $target);
        if ($completed && empty($options['force'])) {
            return array('status' => 'unchanged', 'runId' => $completed['id'], 'fingerprint' => $fingerprint);
        }
        $runId = $this->runs->start($fingerprint, $consumerModule, $target);
        try {
            $reference = $consumer->consume($document, $options);
            $this->runs->complete($runId, $reference);
            return array('status' => 'completed', 'runId' => $runId, 'fingerprint' => $fingerprint, 'result' => $reference);
        } catch (Throwable $error) {
            $issues = array(array('severity' => 'error', 'code' => 'delivery.failed', 'message' => $error->getMessage(), 'path' => 'delivery'));
            $this->runs->fail($runId, $issues);
            return array('status' => 'failed', 'runId' => $runId, 'issues' => $issues);
        }
    }

    public function applyCapability(array $document, $module, $class, array $options = array())
    {
        $document = $this->validator->validate($document);
        if (!$document['valid']) {
            return $document;
        }
        try {
            return $this->getObject($class, $module)->transform($document, $options);
        } catch (Throwable $error) {
            $document['valid'] = false;
            $document['issues'][] = array(
                'severity' => 'error',
                'code' => 'capability.failed',
                'message' => $error->getMessage(),
                'path' => 'capability'
            );
            return $document;
        }
    }

    private function failure($code, $message, $path)
    {
        return array('schema' => 'chisimba.ingest-document/v1', 'valid' => false, 'blocks' => array(), 'assets' => array(), 'issues' => array(
            array('severity' => 'error', 'code' => $code, 'message' => $message, 'path' => $path)
        ));
    }
}
?>
