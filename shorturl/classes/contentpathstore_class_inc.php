<?php
/** One namespace for permanent content paths. Persistence stays behind dbTable. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class contentpathstore extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback') {parent::init('tbl_shorturl_content',$pearDb,$errorCallback);}
 public function locked(callable $operation) {return $this->withDatabaseLock('shorturl-edit',$operation);}
 public function owner(string $module,string $id): ?array {return $this->selectPrepared('SELECT * FROM tbl_shorturl_content WHERE module_id=? AND content_id=? AND canonical=1',[$module,$id])[0]??null;}
 public function path(string $slug): ?array {return $this->selectPrepared('SELECT * FROM tbl_shorturl_content WHERE slug=?',[$slug])[0]??null;}
 public function legacyPathExists(string $slug): bool {return (bool)$this->selectPrepared('SELECT id FROM tbl_shorturl_map WHERE LOWER(match_url) IN (?, ?)',[$slug,$slug.'/']);}
 public function candidates(string $module): array {
  return match($module) {
   'sitepages'=>$this->selectPrepared("SELECT id,title,slug FROM tbl_sitepages WHERE status='published' ORDER BY id"),
   'simpleblog'=>$this->selectPrepared("SELECT id,post_title AS title FROM tbl_simpleblog_posts WHERE post_type='site' AND post_status='posted' ORDER BY id"),
   'webinar'=>$this->selectPrepared("SELECT id,title FROM tbl_webinar_records WHERE kind='webinar' AND status='published' ORDER BY id"),
   default=>throw new InvalidArgumentException('Unknown content owner'),
  };
 }
 public function retainPageAlias(string $id,string $slug): void {
  // Old query-by-slug URLs may coexist with an existing QR redirect. They are
  // aliases only; canonical paths must still pass the shared collision rules.
  $row=$this->path($slug);
  if($row){if($row['module_id']!=='sitepages'||$row['content_id']!==$id)throw new DomainException('slug_taken');return;}
  if(!$this->insert(['id'=>bin2hex(random_bytes(16)),'slug'=>$slug,'module_id'=>'sitepages','content_id'=>$id,'canonical'=>0]))throw new RuntimeException('save_failed');
 }
 public function claim(string $module,string $id,string $slug): void {
  // Caller owns the namespace lock until the enclosing content transaction commits.
  $this->transactionWithSavepoint(function()use($module,$id,$slug){
   $row=$this->path($slug);
   if($row&&($row['module_id']!==$module||$row['content_id']!==$id))throw new DomainException('slug_taken');
   if($this->executePrepared('UPDATE tbl_shorturl_content SET canonical=0 WHERE module_id=? AND content_id=?',[$module,$id])===false)throw new RuntimeException('save_failed');
   if($row){if($this->update('id',$row['id'],['canonical'=>1])===false)throw new RuntimeException('save_failed');}
   elseif(!$this->insert(['id'=>bin2hex(random_bytes(16)),'slug'=>$slug,'module_id'=>$module,'content_id'=>$id,'canonical'=>1]))throw new RuntimeException('save_failed');
  });
 }
}
