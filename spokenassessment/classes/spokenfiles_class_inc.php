<?php
/** Private recordings are deliberately absent from public File Manager catalogues. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class spokenfiles extends ChisimbaObject
{
    private function directory()
    {
        $configured=$this->getObject('dbsysconfig','sysconfig')->getValue('SECUREFODLER','filemanager');
        $root=is_string($configured)?realpath($configured):false;
        $web=realpath($_SERVER['DOCUMENT_ROOT']??'');
        if (!$root || $root==='/' || ($web && ($root===$web || str_starts_with($root,$web.'/')))) throw new RuntimeException('private_storage');
        $dir=$root.'/spokenassessment';
        if (is_link($dir) || (!is_dir($dir) && !mkdir($dir,0700))) throw new RuntimeException('private_storage');
        if (realpath($dir)!==$dir) throw new RuntimeException('private_storage');
        return $dir;
    }
    public function path($id)
    {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D',$id)) throw new DomainException('unavailable');
        $path=$this->directory().'/'.$id;
        if (is_link($path)) throw new DomainException('unavailable');
        return $path;
    }
    public function upload($id, array $file)
    {
        if (($file['error']??-1)!==UPLOAD_ERR_OK || !is_string($file['tmp_name']??null) || !is_uploaded_file($file['tmp_name'])) throw new DomainException('upload_failed');
        $inspection=$this->getObject('audioinspection','ai')->inspect($file['tmp_name']);
        if (empty($inspection['ok'])) throw new DomainException($inspection['error']);
        $path=$this->path($id);
        if (file_exists($path) || !move_uploaded_file($file['tmp_name'],$path)) throw new RuntimeException('upload_failed');
        chmod($path,0600);
        return $inspection;
    }
    public function remove($id) { $path=$this->path($id); if (is_file($path)) unlink($path); }
    /** Caller must authorise the attempt on every request, including byte ranges. */
    public function deliver(array $attempt)
    {
        $path=$this->path($attempt['id']);
        if (!is_file($path)) { http_response_code(404); exit; }
        $handle=fopen($path,'rb'); if (!$handle) { http_response_code(404); exit; }
        $delivery=$this->getObject('filedelivery','filemanager');
        $plan=$delivery::plan(fstat($handle)['size'],$_SERVER['REQUEST_METHOD']??'GET',$_SERVER['HTTP_RANGE']??null);
        http_response_code($plan['status']); header('Cache-Control: private, no-store'); header('X-Content-Type-Options: nosniff');
        header('Accept-Ranges: bytes'); header('Content-Length: '.$plan['length']);
        header('Content-Type: '.(new finfo(FILEINFO_MIME_TYPE))->file($path));
        header('Content-Disposition: inline; filename="recording"');
        if (isset($plan['contentRange'])) header('Content-Range: '.$plan['contentRange']);
        if ($plan['status']===405) header('Allow: GET, HEAD');
        if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
        if ($plan['body']) { fseek($handle,$plan['offset']); $left=$plan['length']; while ($left>0 && !feof($handle)) { $chunk=fread($handle,min(65536,$left)); if (!$chunk) break; echo $chunk; $left-=strlen($chunk); } }
        fclose($handle); exit;
    }
}
