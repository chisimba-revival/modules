<?php
/** Paid file access uses the original registered file and shared protected delivery.
 * @author Derek Keats <derek@dkeats.com>
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class shopdownloads extends ChisimbaObject
{
    public function init() { $this->loadClass('filedelivery','filemanager'); }
    private function file($id)
    {
        if (!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id)) throw new DomainException('invalid_download');
        $file=$this->getObject('dbfile','filemanager')->getFile($id);
        // Course and assignment materials must not be repurposed as shop products.
        if (!$file || !str_starts_with($file['filefolder']??'','users/')
            || ($file['access']??'')!=='private_selected') throw new DomainException('private_download_required');
        $secure=$this->getObject('dbsysconfig','sysconfig')->getValue('SECUREFODLER','filemanager');
        if (!filedelivery::resolve($secure,$file['path']??'')) throw new DomainException('private_download_required');
        if (filedelivery::resolve($this->getObject('altconfig','config')->getcontentBasePath(),$file['path']??'')) throw new DomainException('private_download_required');
        return $file;
    }
    public function validateFile($id): array
    {
        $this->getObject('shopservice','shop')->requireManager();
        $file=$this->file($id);
        if (!$this->getObject('filereadpolicy','filemanager')->mayRead($file)) throw new DomainException('forbidden');
        return $file;
    }
    /** Called by File Manager for every download, including HEAD and byte ranges. */
    public function mayDownloadFile(array $file, $orderId): bool
    {
        try {
            $registered=$this->file($file['id']);
            if ($registered['path']!==$file['path']) return false;
            $this->getObject('shopservice','shop')->downloadOrder($orderId,$file['id']);
            return true;
        } catch (DomainException $error) { return false; }
    }
    public function send($orderId,$fileId)
    { $this->getObject('filedelivery','filemanager')->sendOwnedFile($fileId,'shop','shopdownloads',$orderId); }
}
