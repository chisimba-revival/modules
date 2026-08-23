<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class ingeststagingservice extends ChisimbaObject
{
    const MAX_AGE = 7200;

    public function stageUpload(array $upload, $ownerId)
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || empty($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
            throw new InvalidArgumentException('source.upload_failed');
        }
        return $this->stageFile($upload['tmp_name'], $upload['name'] ?? '', $ownerId, true);
    }

    public function stageFile($sourcePath, $originalName, $ownerId, $uploaded = false)
    {
        $extension = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, array('docx', 'odt'), true)) {
            throw new InvalidArgumentException('source.unsupported_type');
        }
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new InvalidArgumentException('source.unreadable');
        }
        $directory = $this->directory();
        $this->removeExpired($directory);
        $token = bin2hex(random_bytes(24));
        $target = $directory . '/' . $token . '.' . $extension;
        $stored = $uploaded ? move_uploaded_file($sourcePath, $target) : copy($sourcePath, $target);
        if (!$stored) { throw new RuntimeException('source.stage_failed'); }
        @chmod($target, 0600);
        $metadata = array(
            'token' => $token,
            'owner' => hash('sha256', (string) $ownerId),
            'name' => basename((string) $originalName),
            'extension' => $extension,
            'created' => time(),
            'fingerprint' => hash_file('sha256', $target)
        );
        if (file_put_contents($directory . '/' . $token . '.json', json_encode($metadata), LOCK_EX) === false) {
            @unlink($target);
            throw new RuntimeException('source.stage_failed');
        }
        return $metadata;
    }

    public function resolve($token, $ownerId)
    {
        if (!preg_match('/^[a-f0-9]{48}$/', (string) $token)) {
            throw new InvalidArgumentException('source.stage_invalid');
        }
        $metadataPath = $this->directory() . '/' . $token . '.json';
        $metadata = is_file($metadataPath) ? json_decode((string) file_get_contents($metadataPath), true) : null;
        if (!is_array($metadata) || !hash_equals((string) ($metadata['owner'] ?? ''), hash('sha256', (string) $ownerId))
            || time() - (int) ($metadata['created'] ?? 0) > self::MAX_AGE) {
            throw new InvalidArgumentException('source.stage_expired');
        }
        $path = $this->directory() . '/' . $token . '.' . ($metadata['extension'] ?? '');
        if (!is_file($path) || !hash_equals((string) $metadata['fingerprint'], (string) hash_file('sha256', $path))) {
            throw new InvalidArgumentException('source.stage_invalid');
        }
        $metadata['path'] = $path;
        return $metadata;
    }

    public function discard($token, $ownerId)
    {
        $metadata = $this->resolve($token, $ownerId);
        @unlink($metadata['path']);
        @unlink($this->directory() . '/' . $token . '.json');
    }

    private function directory()
    {
        $directory = rtrim(sys_get_temp_dir(), '/') . '/chisimba-ingestservice';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('source.stage_failed');
        }
        return $directory;
    }

    private function removeExpired($directory)
    {
        foreach (glob($directory . '/*.json') ?: array() as $metadataPath) {
            if (filemtime($metadataPath) >= time() - self::MAX_AGE) { continue; }
            $token = basename($metadataPath, '.json');
            foreach (glob($directory . '/' . $token . '.*') ?: array() as $path) { @unlink($path); }
        }
    }
}
?>
